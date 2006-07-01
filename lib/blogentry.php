<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005 Peter A. Geer <pageer@skepticats.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

require_once("blogconfig.php");
require_once("lib/utils.php");
require_once("lib/creators.php");
require_once("lib/entry.php");

/*
Class: BlogEntry
Represents an entry in the weblog.

Inherits:
<LnBlogObject>, <Entry>

Events:
OnInit         - Fired when object is first created.
InitComplete   - Fired at end of constructor.
OnInsert       - Fired before object is saved to persistent storage.
InsertComplete - Fired after object has finished saving.
OnDelete       - Fired before object is deleted.
DeleteComplete - Fired after object is deleted.
OnUpdate       - Fired before changes are saved to persistent storage.
UpdateComplete - Fired after changes to object are saved.
OnOutput       - Fired before output is generated.
OutputComplete - Fired after output has finished being generated.
POSTRetrieved  - Fired after data has been retreived from an HTTP POST.
*/

class BlogEntry extends Entry {
	
	var $allow_comment = true;
	var $allow_tb = true;
	var $has_html;
	var $mail_notify = true;
	var $sent_ping = true;
	var $abstract;

	function BlogEntry ($path="", $revision=ENTRY_DEFAULT_FILE) {
		
		$this->raiseEvent("OnInit");
	
		$this->id = '';
		$this->uid = '';
		$this->ip = get_ip();
		$this->date = "";
		$this->post_date = false;
		$this->timestamp = "";
		$this->post_ts = false;
		$this->mail_notify = true;
		$this->sent_ping = true;
		$this->subject = "";
		$this->tags = "";
		$this->data = "";
		#$this->abstract = "";
		$this->has_html = MARKUP_BBCODE;
		$this->allow_comment = true;
		$this->allow_tb = true;
		$this->template_file = ENTRY_TEMPLATE;
		$this->metadata_fields = array("id"=>"postid", "uid"=>"userid", 
			"date"=>"date", "post_date"=>"postdate",
			"timestamp"=>"timestamp", "post_ts"=>"posttimestamp",
			"ip"=>"ip", "mail_notify"=>"mail notification",
			"sent_ping"=>"trackback ping", "subject"=>"subject",
			"abstract"=>"abstract", "allow_comment"=>"allowcomment",
			"has_html"=>"hashtml", "tags"=>"tags", 
			"allow_tb"=>"allowtrackback");
		
		# Auto-detect the current entry.  If no path is given, 
		# then assume the current directory.
		if ($path && file_exists($path)) {
		
			$this->file = $path.PATH_DELIM.$revision;
			
		} elseif (GET("entry") || $path) {

			$entrypath = trim($path ? $path : GET("entry"));
			
			# Get the blog path from the query string.
			if (defined("BLOG_ROOT")) {
				$blogpath = BLOG_ROOT;
			} elseif ( defined("INSTALL_ROOT") && sanitize(GET("blog")) ) {
				$blogpath = calculate_document_root().PATH_DELIM.sanitize(GET("blog"));
			} else {
				$blogpath = "";
			}

			# Get the path from the entry field.  If the entry string is 
			# malformed, then just empty it.
			if ( preg_match('/^\d{4}\/\d{2}\/\d{2}_\d{4}\d?\d?$/', $entrypath) )
				$entrypath = str_replace("/", PATH_DELIM, $entrypath );
			else $entrypath = "";

			$this->file = mkpath($blogpath,BLOG_ENTRY_PATH,$entrypath,$revision);
			
		} else {
		
			$this->file = getcwd().PATH_DELIM.$revision;
			# We might be in a comment or trackback directory, 
			if (! $this->isEntry() ) {
				$this->file = dirname(getcwd()).PATH_DELIM.$revision;
				if (! $this->isEntry() ) {
					$this->file = getcwd().PATH_DELIM.$revision;
				}
			}
			
		}
		
		if ( file_exists($this->file) ) {
			$this->readFileData();
		}
		
		$this->raiseEvent("InitComplete");
	}
	
	/*
	Method: getParent
	Get a copy of the parent of this objcet, i.e. the blog to which it
	belongs.

	Returns:
	A Blog object.
	*/
	
	function getParent() {
		if (file_exists($this->file)) {
			$dir = $this->file;
			# If this is an article, the blog is 3 levels up.
			for ($i=0; $i<3; $i++) $dir = dirname($dir);
			$ret = NewBlog($dir);
			if (!$ret->isBlog()) {
				# If the entry is a BlogEntry, then the parent will be 2 more 
				# (i.e. 5) levels up
				for ($i=0; $i<2; $i++) $dir = dirname($dir);
				$ret = NewBlog($dir);
			}
		} else $ret = NewBlog();
		return $ret;
	}

	# Method: entryID
	# Gets an identifier for the current entry.
	#
	# Returns:
	# For file-based storage, string containing the last part of the path.
	# Normally, this is in the form ##/##/##_####

	function entryID() {
		$temp = dirname($this->file);
		$ret = basename($temp);  # Add day component.
		$temp = dirname($temp);
		$ret = basename($temp)."/".$ret;  # Add month component.
		$temp = dirname($temp);
		$ret = basename($temp)."/".$ret;  # Add year component.
		return $ret;
	}

	# Method: globalID
	# A unique ID for use with blogging APIs.  Note that this ID embedds the 
	# parent blog's ID, whereas the <entryID> method provides an ID *within*
	# the current blog.
	# 
	# Returns:
	# A string with the unique ID.

	function globalID() {
		$ret = str_replace(PATH_DELIM, '/', 
		                   substr(dirname($this->file), 
		                          strlen(DOCUMENT_ROOT)));
		return trim($ret, '/');
	}

	# Method: parentID
	# Gets an identifier for the entry's parent blog.
	#
	# Returns:
	# For file-based storage, returns the webroot-relative path to the blog.

	function parentID() {
		$path = dirname($this->file);
		$num_levels = is_a($this, 'Article') ? 2 : 4;
		for ($i = 0; $i < $num_levels; $i++) $path = dirname($path);
		return substr($path, strlen(DOCUMENT_ROOT));
	}
	
	# Method: getUploadedFiles
	# Gets an array of the names of all files uploaded to this entry.  
	# Currently, this just means all files in the entry directory that were not
	# created by LnBlog.
	#
	# Returns:
	# An array containing the file names without path.
	
	function getUploadedFiles() {
		$base_path = $this->localpath();
		$std_files = array('index.php','config.php','edit.php','delete.php',
		                   'trackback.php','uploadfile.php',ENTRY_DEFAULT_FILE);
		$files = scan_directory($base_path);
		
		$ret = array();
		
		foreach ($files as $f) {
			if (!in_array($f, $std_files) && !is_dir(mkpath($base_path, $f))) {
				$ret[] = $f;
			}
		}
	}

	/*
	Method: getPath
	Returns a path to use for the entry.  This is only applicable for 
	file-based storage and is for *internal use only*.

	Parameters:
	curr_ts     - The timestamp for this entry.
	just_name   - *Optional* boolean determining whether to return a full path
	              or just a file name.  Default is false.
	long_format - *Optional* boolean for whether to use a long or regular
	              date format.  Defaults to false.

	Returns:
	A string with the path to use for this entry.
	*/

	function getPath($curr_ts, $just_name=false, $long_format=false) {
		$year = date("Y", $curr_ts);
		$month = date("m", $curr_ts);
		$fmt = $long_format ? ENTRY_PATH_FORMAT_LONG : ENTRY_PATH_FORMAT;
		$base = date($fmt, $curr_ts);
		if ($just_name) return $base;
		else return $year.PATH_DELIM.$month.PATH_DELIM.$base;
	}

	/*
	Method: isEntry
	Determine if the object is a blog entry or not.

	Parameter:
	path - *Optional* path to the entry.  If not set, use the current object.

	Return:
	True if the object is an existing entry, false otherwise.
	*/
	function isEntry ($path=false) {
		if (! $path) $path = dirname($this->file);
		return file_exists($path.PATH_DELIM.ENTRY_DEFAULT_FILE);
	}
	
	/*
	Method: localpath
	Get the path to this entry's directory on the local filesystem.  Note 
	that this is specific to file-based storage and so should only be c
	called internally.

	Returns:
	A string representing a path to the object or false on failure.
	*/
	function localpath() {
		if (! $this->isEntry()) return false;
		return dirname($this->file);
	}

	/*
	Method: permalink
	Get the permalink to the object.
	
	Returns:
	A string containing the full URI to this entry.
	*/
	function permalink() {
		return $this->uri("page");
	}

	# Method: baselink
	# Returns a link to the object's base directory.
	#
	# Returns:
	# A string with the URI.

	function baselink() {
		return $this->uri("base");
	}	

	# Method: commentlink
	# Get the permalink to the object.
	
	# Returns:
	# A string containing the full URI to this entry.
	
	function commentlink() {
		return $this->uri("comment");
	}
	
	/*
	Method: uri
	Gets the URI of the a specified page.
	
	Parameters:
	link - The page for which you want the URI.  Valid values are
	       "page", "
	
	Returns:
	A string holding the relevant URI.
	*/
	function uri($type) {
		$dir_uri = localpath_to_uri($this->localpath());
		switch ($type) {
			case "permalink":
			case "entry":
			case "page": 
				$pretty_file = $this->calcPrettyPermalink();
				$pretty_file = dirname($this->localpath()).PATH_DELIM.$pretty_file;
				if ( file_exists($pretty_file) ) {
					return localpath_to_uri($pretty_file);
				} else {
					$pretty_file = $this->calcPrettyPermalink(true);
					$pretty_file = dirname($this->localpath()).PATH_DELIM.$pretty_file;
					if ( file_exists($pretty_file) ) {
						return localpath_to_uri($pretty_file);
					} else {
						return $dir_uri;
					}
				}
			case "send_tb":     return $dir_uri."trackback.php?send_ping=yes";
			case "get_tb":      return $dir_uri."trackback.php";
			case "trackback":   return $dir_uri.ENTRY_TRACKBACK_DIR."/";
			case "upload":      return $dir_uri."uploadfile.php";
			case "edit":        return $dir_uri."edit.php";
			case "delete":     
				if (KEEP_EDIT_HISTORY) {
					return $dir_uri."delete.php";
				} else {
					$entry_type = is_a($this, 'Article') ? 'article' : 'entry';
					return make_uri(INSTALL_ROOT_URL."pages/delentry.php", 
					                array("blog"     =>$this->parentID(), 
					                      $entry_type=>$this->entryID()));
				}
			case "comment":     return $dir_uri.ENTRY_COMMENT_DIR."/";
			case "commentpage": return $dir_uri.ENTRY_COMMENT_DIR."/index.php";
			case "base":        return $dir_uri;
			case "basepage":    return $dir_uri."index.php";
		}
		return $dir_uri;
	}
	
	function getByPath ($path, $revision=ENTRY_DEFAULT_FILE) {
		$file_path = $path.PATH_DELIM.$revision;
		return $this->readFileData($file_path); 
	}
	
	/*
	Method: update
	Commit changes to the object.

	Returns:
	True on success, false on failure.
	*/
	function update () {
		
		$this->raiseEvent("OnUpdate");
		
		$dir_path = dirname($this->file);
		$this->ip = get_ip();
		$curr_ts = time();
		$this->date = fmtdate(ENTRY_DATE_FORMAT, $curr_ts);
		$this->timestamp = $curr_ts;

		$target = $dir_path.PATH_DELIM.
			$this->getPath($curr_ts, true, true).ENTRY_PATH_SUFFIX;
		$source = $dir_path.PATH_DELIM.ENTRY_DEFAULT_FILE;

		$fs = NewFS();
		$ret = $fs->rename($source, $target);
			
		if ($ret) $ret = $this->writeFileData();

		# Create wrappers for comments and trackbacks if they do not exist.
		# This is done here for the article subclass, as it did not previously 
		# create these wrappers.
		if ($this->allow_comment && 
		    ! is_dir($dir_path.PATH_DELIM.ENTRY_COMMENT_DIR) ) {
			create_directory_wrappers($dir_path.PATH_DELIM.ENTRY_COMMENT_DIR, ENTRY_COMMENTS);
		}
		if ($this->allow_tb &&
		    ! is_dir($dir_path.PATH_DELIM.ENTRY_TRACKBACK_DIR) ) {
			create_directory_wrappers($dir_path.PATH_DELIM.ENTRY_TRACKBACK_DIR, ENTRY_TRACKBACKS);
		}

		if (! $ret) {
			
			$ret = $fs->rename($target, $source);
			$fs->destruct();
			return false;
			
		} else {
		
			if (! KEEP_EDIT_HISTORY) $fs->delete($target);
			
			$subfile = $this->calcPrettyPermalink();
			if ($subfile) {
				$subfile = dirname(dirname($this->file)).PATH_DELIM.$subfile;
				if (! file_exists($subfile)) $this->makePrettyPermalink();
			}
		}
		$fs->destruct();
		$this->raiseEvent("UpdateComplete");
		return $ret;
	}

	/* 
	Method: delete
	Delete the current object.

	Returns:
	True on success, false on failure.
	*/
	function delete () {
		
		$fs = NewFS();
		$curr_ts = time();
		$dir_path = dirname($this->file);
		if (! $this->isEntry($dir_path) ) return false;
		
		$this->raiseEvent("OnDelete");
		
		$subfile = $this->calcPrettyPermalink();
		if (file_exists($subfile)) {
			$fs->delete($subfile);
		} else {
			$subfile = $this->calcPrettyPermalink(true);
			if (file_exists($subfile)) $fs->delete($subfile);
		}
		
		if (KEEP_EDIT_HISTORY) {
			$source_file = $dir_path.PATH_DELIM.ENTRY_DEFAULT_FILE;
			$target_file = $dir_path.PATH_DELIM.
				$this->getPath($curr_ts, true, true).ENTRY_PATH_SUFFIX;
			$ret = $fs->rename($source_file, $target_file);
		} else {
			$ret = $fs->rmdir_rec($this->localpath());
		}
		$fs->destruct();
		
		$this->raiseEvent("DeleteComplete");
		
		return $ret;
	}

	/*
	Method: insert
	Save the object to persistent storage.
	
	Parameters:
	blog - The blog into which the entry will be inserted.

	Returns:
	True on success, false on failure.
	*/
	function insert (&$blog) {
	
		if (! $this->uid) {
			$usr = NewUser();
			$this->uid = $usr->username();
		}
	
		$curr_ts = time();
		
		$basepath = $blog->home_path.PATH_DELIM.BLOG_ENTRY_PATH;
		$dir_path = $basepath.PATH_DELIM.$this->getPath($curr_ts);
		
		# If the entry driectory already exists, something is wrong. 
		if ( is_dir($dir_path) ) 
			$dir_path = $basepath.PATH_DELIM.$this->getPath($curr_ts, false, true);
		if ( is_dir($dir_path) ) return false;
		
		$this->raiseEvent("OnInsert");

		# First, check that the year and month directories exist and have
		# the appropriate wrapper scripts in them.
		$month_path = dirname($dir_path);
		$year_path = dirname($month_path);
		if (! is_dir($year_path)) $ret = create_directory_wrappers($year_path, YEAR_ENTRIES);
		if (! is_dir($month_path)) $ret = create_directory_wrappers($month_path, MONTH_ENTRIES);
		$ret = create_directory_wrappers($dir_path, ENTRY_BASE);

		# Create the comments directory.
		create_directory_wrappers($dir_path.PATH_DELIM.ENTRY_COMMENT_DIR, ENTRY_COMMENTS);
		create_directory_wrappers($dir_path.PATH_DELIM.ENTRY_TRACKBACK_DIR, ENTRY_TRACKBACKS);
				
		$this->file = $dir_path.PATH_DELIM.ENTRY_DEFAULT_FILE;
		# Set the timestamp and date, plus the ones for the original post, if
		# this is a new entry.
		$this->date = fmtdate(ENTRY_DATE_FORMAT, $curr_ts);
		if (! $this->post_date) 
			$this->post_date = fmtdate(ENTRY_DATE_FORMAT, $curr_ts);
		$this->timestamp = $curr_ts;
		if (! $this->post_ts) $this->post_ts = $curr_ts;
		$this->ip = get_ip();

		$ret = $this->writeFileData();
		# Add a wrapper file to make the link prettier.
		if ($ret) {
			$this->id = $this->globalID();
			$this->makePrettyPermalink();
		}
		$this->raiseEvent("InsertComplete");

		return $ret;
		
	}
	
	/*
	Method: calcPrettyPermalink
	Calculates a file name for a "pretty" permalink wrapper script.
	
	Parameters:
	use_broken_regex - *Optional* parameter to calculate the URI based on the
	                   ugly regex used in LnBlog < 0.7.  *Defaults* to false.
	
	Returns:
	The string to be used for the file name.
	*/
	function calcPrettyPermalink($use_broken_regex=false) {
		$ret = trim($this->subject);
		if (!$use_broken_regex) {
			$ret = str_replace(array("'", '"'), "_", $ret);
			$ret = preg_replace("/[\W_]+/", "_", $ret);
		} else {
			$ret = preg_replace("/\W/", "_", $ret);
		}
		if ($ret) $ret .= ".php";
		return $ret;
	}
	
	/*
	Method: makePrettyPermalink
	Creates a wrapper script that makes a "pretty" permalink to the entry
	directory based on the subject text of the entry.
	
	Returns:
	True on success, false on failure.
	*/
	function makePrettyPermalink() {
		$subfile = $this->calcPrettyPermalink();
		if ($subfile) {
			# Put the wrapper in the parent of the entry directory.
			$path = dirname(dirname($this->file));
			$dir_path = basename(dirname($this->file));
			$path .= PATH_DELIM.$subfile;
			$ret = write_file($path, "<?php chdir('".$dir_path."'); include('config.php'); include('index.php'); ?>");
		} else $ret = false;
		return $ret;
	}

	/*
	Method: getPostData
	Extract data from an HTTP POST and insert it into the object.
	The data fields in the POST are described below.

	Fields:
	subject    - The subject of the entry, in plain text.
	short_path - The "short path" to access an article.
	abstract   - An abstract of the entry, with markup.
	tags       - A comma-delimited list of free-form tags.
	body       - The entry body, with markup.
	comments   - Boolean representing whether new comments can be posted.
	input_mode - Tristate variable representing the type of markup used.
	             Valid values are defined by the constants <MARKUP_NONE>,
					 <MARKUP_BBCODE>, and <MARKUP_HTML>.
	*/
	function getPostData() {
		if (! has_post()) return false;
		$this->subject = POST("subject");
		$this->abstract = POST("abstract");
		$this->tags = POST("tags");
		$this->data = POST("body");
		$this->allow_comment = POST("comments") ? 1 : 0;
		$this->allow_tb = POST("trackbacks") ? 1 : 0;
		$this->has_html = POST("input_mode");
		foreach ($this->custom_fields as $fld=>$desc) {
			$this->$fld = POST($fld);
		}
		if (get_magic_quotes_gpc()) {
			$this->subject = stripslashes($this->subject);
			$this->tags = stripslashes($this->tags);
			$this->data = stripslashes($this->data);
			foreach ($this->custom_fields as $fld=>$desc) {
				$this->$fld = stripslashes($this->$fld);
			}
		}
		if (! $this->uid) {
			$u = NewUser();
			$this->uid = $u->username();
		}
		$this->raiseEvent("POSTRetreived");
	}

	/*
	Method: get
	Get the HTML to use to display the object.

	Parameters:
	show_edit_controls - *Optional* boolean determining if the edit, delete,
	                     etc. links should be displayed.  *Defaults* to false.

	Returns:
	A string containing the markup.
	*/
	function get($show_edit_controls=false) {
		ob_start();
		$this->raiseEvent("OnOutput");
		$ret = ob_get_contents();
		ob_end_clean();
	
		$tmp = NewTemplate($this->template_file);
		$blog = $this->getParent();
		$usr = NewUser($this->uid);
		$usr->exportVars($tmp);
		
		$tmp->set("SUBJECT", $this->subject);
		$tmp->set("TITLE", $this->subject);  # For article compatibility.
		$tmp->set("POSTDATE", $this->prettyDate($this->post_ts) );
		$tmp->set("POST_TIMESTAMP", $this->post_ts);
		$tmp->set("EDITDATE", $this->prettyDate() );
		$tmp->set("EDIT_TIMESTAMP", $this->timestamp);
		$tmp->set("ABSTRACT", $this->abstract);
		$tmp->set("TAGS", $this->tags());
		$tmp->set("BODY", $this->markup() );
		$tmp->set("ALLOW_COMMENTS", $this->allow_comment);
		$tmp->set("ALLOW_TRACKBACKS", $this->allow_tb);
		$tmp->set("PERMALINK", $this->permalink() );
		$tmp->set("PING_LINK", $this->uri("send_tb"));
		$tmp->set("TRACKBACK_LINK", $this->uri("get_tb"));
		$tmp->set("UPLOAD_LINK", $this->uri("upload"));
		$tmp->set("EDIT_LINK", $this->uri("edit"));
		$tmp->set("DELETE_LINK", $this->uri("delete"));
		$tmp->set("TAG_LINK", $blog->uri('tags'));
		$tmp->set("COMMENTCOUNT", $this->getCommentCount() );
		$tmp->set("COMMENT_LINK", $this->uri("comment"));
		$tmp->set("TRACKBACKCOUNT", $this->getTrackbackCount() );
		$tmp->set("SHOW_TRACKBACK_LINK", $this->uri("trackback"));
		$tmp->set("SHOW_CONTROLS", $show_edit_controls);

		foreach ($this->custom_fields as $fld=>$desc) {
			$tmp->set(strtoupper($fld), isset($this->$fld) ? $this->$fld : '');
			$tmp->set(strtoupper($fld)."_DESC", $desc);
		}
		
		$ret .= $tmp->process();
		ob_start();
		$this->raiseEvent("OutputComplete");
		$ret .= ob_get_contents();
		ob_end_clean();
		return $ret;
	}

	# Comment handling functions.

	/*
	Method: getCommentCount
	Determine the number of comments that belong to this object.

	Returns:
	A non-negative integer representing the number of comments.
	*/
	function getCommentCount() {
		$dir_path = dirname($this->file);
		$comment_dir_path = $dir_path.PATH_DELIM.ENTRY_COMMENT_DIR;
		$comment_dir = scan_directory($comment_dir_path);
		if ($comment_dir === false) return false;
		
		$count = 0;
		foreach ($comment_dir as $file) {
			$cond = is_file($comment_dir_path.PATH_DELIM.$file) && 
			        preg_match("/[\w\d]+".COMMENT_PATH_SUFFIX."/", $file);
			if ($cond) $count++;
		}
		return $count;

	}

	/*
	Method: getCommentArray
	Gets all the comment objects for this entry.

	Parameters:
	sort_asc - *Optional* boolean (true by default) determining whether the
	           comments should be sorted in ascending order by date.
	
	Returns:
	An array of BlogComment object.
	*/
	function getCommentArray($sort_asc=true) {
		$dir_path = dirname($this->file);
		$comment_dir_path = $dir_path.PATH_DELIM.ENTRY_COMMENT_DIR;
		if (! is_dir($comment_dir_path)) return false;
		else $comment_dir = scan_directory($comment_dir_path);
		
		$comment_files = array();
		foreach ($comment_dir as $file) {
			$cond = is_file($comment_dir_path.PATH_DELIM.$file) && 
			        preg_match("/[\w\d]+".COMMENT_PATH_SUFFIX."/", $file);
			if ($cond) $comment_files[] = $file; 
		}
		if ($sort_asc) sort($comment_files);
		else rsort($comment_files);

		$comment_array = array();
		foreach ($comment_files as $file)
			$comment_array[] = NewBlogComment($comment_dir_path.PATH_DELIM.$file);
		
		return $comment_array;
	}

	/*
	Method: getComments
	Get the HTML markup to display the entire list of comments for this entry.

	Parameters:
	sort_asc - *Optional* boolean (true by default) determining whether the
	           comments should be sorted in ascending order by date.
	
	Returns:
	A string of HTML markup.
	*/
	function getComments($sort_asc=true) {
		$comment_files = $this->getCommentArray($sort_asc);
		$ret = "";
		if ($comment_files) {
			$comments = array();
			foreach ($comment_files as $file) {
				$comments[] = $file->get();
			}
		}

		# Suppress the comment stuff entirely for posts that don't have 
		# any comments.
		if (isset($comments)) {
			$tpl = NewTemplate(LIST_TEMPLATE);
			$tpl->set("ITEM_CLASS", "fullcomment");
			$tpl->set("ORDERED");
			$tpl->set("LIST_TITLE", 
			          spf_('Comments on <a href="%s">%s</a>', 
				            $this->permalink(), $this->subject));
			$tpl->set("ITEM_LIST", $comments);
			$ret = $tpl->process();
		}

		return $ret;
	}

	# TrackBack handling functions.

	/*
	Method: getTrackbackCount
	Get the number of TrackBacks for this object.

	Returns:
	A non-negative integer representing the number of TrackBacks.
	*/
	function getTrackbackCount() {
		$dir_path = dirname($this->file);
		$tb_path = $dir_path.PATH_DELIM.ENTRY_TRACKBACK_DIR;
		$tb_dir_content = scan_directory($tb_path);
		if ($tb_dir_content === false) return false;
		
		$count = 0;
		foreach ($tb_dir_content as $file) {
			$cond = is_file($tb_path.PATH_DELIM.$file) && 
			        preg_match("/[\w\d]+".TRACKBACK_PATH_SUFFIX."/", $file);
			if ($cond) $count++;
		}
		return $count;
	}

	/*
	Method: getTrackbackArray
	Get an array of Trackback objects that contains all TrackBacks for 
	this entry.

	Parameters:
	sort_asc - *Optional* boolean (true by default) determining whether the
	           trackbacks should be sorted in ascending order by date.
	
	Returns:
	An array of Trackback objects.
	*/
	function getTrackbackArray($sort_asc=true) {
		$dir_path = dirname($this->file);
		$tb_path = $dir_path.PATH_DELIM.ENTRY_TRACKBACK_DIR;
		if (! is_dir($tb_path)) return false;
		else $tb_dir_content = scan_directory($tb_path);
		
		$tb_files = array();
		foreach ($tb_dir_content as $file) {
			$cond = is_file($tb_path.PATH_DELIM.$file) && 
			        preg_match("/[\w\d]+".TRACKBACK_PATH_SUFFIX."/", $file);
			if ($cond) $tb_files[] = $file; 
		}
		if ($sort_asc) sort($tb_files);
		else rsort($tb_files);

		$tb_array = array();
		foreach ($tb_files as $file) 
			$tb_array[] = NewTrackback($tb_path.PATH_DELIM.$file);
		
		return $tb_array;
	}

	/*
	Method: getTrackbacks
	Get some HTML that displays the TrackBacks for this entry.

	Parameters:
	sort_asc - *Optional* boolean (true by default) determining whether the
	           trackbacks should be sorted in ascending order by date.
	
	Returns:
	A string of markup.
	*/				  
	function getTrackbacks($sort_asc=true) {
		$trackbacks = $this->getTrackbackArray($sort_asc);	
		if (!$trackbacks) return "";
		$ret = "";
		if ($trackbacks) {
			$tbtext = array();
			foreach ($trackbacks as $tb) {
				$tbtext[] = $tb->get();
			}
		}
		
		if (isset($tbtext)) {
			$tpl = NewTemplate(LIST_TEMPLATE);
			$tpl->set("LIST_CLASS", "tblist");
			$tpl->set("ITEM_CLASS", "trackback");
			$tpl->set("ORDERED");
			$tpl->set("LIST_TITLE", 
			          spf_('TrackBacks for <a href="%s">%s</a>',
			               $this->permalink(), $this->subject));
			$tpl->set("ITEM_LIST", $tbtext);
			$ret = $tpl->process();
		}
		
		return $ret;
	}

}
?>
