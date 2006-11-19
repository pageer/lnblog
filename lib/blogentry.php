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
		
		$this->initVars();
		$this->getFile($path, $revision);
		
		if ( file_exists($this->file) ) {
			$this->readFileData();
		}
		
		$this->raiseEvent("InitComplete");
	}
	
	# Initializes the member variables.
	# This is for INTERNAL USE ONLY and exists mainly to pass on the 
	# variables to subclasses without having to call the entire constructor.
	function initVars() {
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
		$this->abstract = "";
		$this->enclosure = '';
		$this->has_html = MARKUP_BBCODE;
		$this->allow_comment = true;
		$this->allow_tb = true;
		$this->allow_pingback = true;
		$this->template_file = ENTRY_TEMPLATE;
		$this->metadata_fields = array("id"=>"postid", "uid"=>"userid", 
			"date"=>"date", "post_date"=>"postdate",
			"timestamp"=>"timestamp", "post_ts"=>"posttimestamp",
			"ip"=>"ip", "mail_notify"=>"mail notification",
			"sent_ping"=>"trackback ping", "subject"=>"subject",
			"abstract"=>"abstract", "allow_comment"=>"allowcomment",
			"has_html"=>"hashtml", "tags"=>"tags", 
			"allow_tb"=>"allowtrackback", 
			"allow_pingback"=>"allowpingback", "enclosure"=>"enclosure");
	}
	
	# Gets the directory and data file for this entry.
	# Again, this is for INTERNAL USE ONLY and is inherited, with parameters,
	# by the Article class.
	function getFile($path, $revision, $getvar='entry', 
	                 $subdir=BLOG_ENTRY_PATH,
		             $id_re='/^\d{4}\/\d{2}\/\d{2}_\d{4}\d?\d?$/') {
		# Auto-detect the current entry.  If no path is given, 
		# then assume the current directory.
		if ($path && is_dir($path)) {
		
			$this->file = $path.PATH_DELIM.$revision;
			
		} elseif (GET($getvar) || $path) {

			$entrypath = trim($path ? $path : sanitize(GET($getvar)));
			
			# Get the blog path from the query string.
			if (defined("BLOG_ROOT")) {
				$blogpath = BLOG_ROOT;
			} elseif ( defined("INSTALL_ROOT") && sanitize(GET("blog")) ) {
				$blogpath = calculate_document_root().PATH_DELIM.sanitize(GET("blog"));
			} else {
				$blogpath = "";
			}

			if ($id_re) {
				# Get the path from the entry field.  If the entry string is 
				# malformed, then just empty it.
				if ( preg_match($id_re, $entrypath) )
					$entrypath = str_replace("/", PATH_DELIM, $entrypath );
				else $entrypath = "";
			}

			$this->file = mkpath($blogpath,$subdir,$entrypath,$revision);
			
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
		return $this->file;
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

	# Method: getEnclosure
	# Gets the URL, file size, and media type of the file set as the enclosure
	# for this entry, it if exists.  The file is checkedy by converting the 
	# URL into a local path using the same rules as entry URL absolutizaiton,
	# or simply doing a URL to localpath conversion.
	#
	# Returns:
	# If the enclosure property is set and the file is found, returns an array 
	# with elements "url", "length", and "type".  If the file is not found, then 
	# checks if the enclosure matches the expected content or an enclosure tag in
	# an RSS feed.  If so, extracts the data into an array.  
	# Otherwise Otherwise, returns false.

	function getEnclosure() {
		# Remove stray whitespace.
		$enc = trim($this->enclosure);

		# Strip any query string.
		if (strpos($enc, '?') !== false) {
			$idx = strpos($enc, '?');
			$enc = substr($enc, 0, $idx);
		}

		if (! $enc) return false;

		# Calculate the enclosure path, using the same rules as LBCode URI 
		# absolutizing.  
		# No slash = relative to entry directory.
		if (strpos($enc, '/') === false) {
			$path = mkpath($this->localpath(), $enc);

		# Slash but not at start = relative to blog directory
		} elseif (! strpos($enc, ':') && substr($enc, 1, 1) != '/') {
			$blog = $this->getParent();
			$path = mkpath($blog->home_path, $enc);
		
		# Slash at start, no colon = root-relatice path
		} elseif (! strpos($enc, ':') && substr($enc, 1, 1) == '/') {
			$path = mkpath(DOCUMENT_ROOT, $enc);
		} else {
			$path = uri_to_localpath($enc);
		}

		if (file_exists($path)) {
			$ret = array();
			$ret['url'] = localpath_to_uri($path);
			$ret['length'] = filesize($path);
			if (extension_loaded("fileinfo")) {
				$mh = finfo_open(FILEINFO_MIME|FILEINFO_PRESERVE_ATIME);
				$ret['type'] = finfo_file($mh, $path);
			} elseif (function_exists('mime_content_type')) {
				$ret['type'] = mime_content_type($path);
			} else {
				# No fileinfo, no mime_magic, so revert to file extension matching.
				# This is a dirty and incomplete method, but I suppose it's better
				# than nothing.  But only marginally.
				$dotpos = strrpos($path, '.');
				$ext = strtolower(substr($path, $dotpos));
				$type = 'application/octet-stream';
				
				switch ($ext) {
					case 'mp3': $type = 'audio/mpeg'; break;
					case 'aif':
					case 'aifc':
					case 'aiff': $type = 'audio/x-aiff'; break;
					case 'm3u': $type = 'audio/x-mpegurl'; break;
					case 'ra': 
					case 'ram': $type = 'audio/x-pn-realaudio'; break;
					case 'wav': $type = 'audio/x-wav'; break;
					case 'mp2':
					case 'mpa':
					case 'mpeg':
					case 'mpe':
					case 'mpg':
					case 'mpv2': $type = 'video/mpeg'; break;
					case 'mov':
					case 'qt': $type = 'video/quicktime'; break;
					case 'asf':
					case 'asr':
					case 'asx': $type = 'video/x-ms-asf'; break;
					case 'avi': $type = 'video/x-msvideo'; break;
					case 'wmv': $type = 'video/x-ms-wmv'; break;
					case 'wma': $type = 'audio/x-ms-wma'; break;
				}

				$ret['type'] = $type;
			}

		} elseif (strpos($this->enclosure, 'url')    !== false && 
		          strpos($this->enclosure, 'length') !== false &&
		          strpos($this->enclosure, 'type')   !== false) {
			$enc = $this->enclosure;
			$ret = array();
			$ret['url'] = preg_replace('/url\s*=\s*"(.+)"/Ui', '$1', $enc);
			$ret['length'] = preg_replace('/length\s*=\s*"(.+)"/Ui', '$1', $enc);
			$ret['type'] = preg_replace('/type\s*=\s*"(.+)"/Ui', '$1', $enc);
		} else $ret = false;

		return $ret;
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
		
		$qs_arr = array();
		
		if (func_num_args() > 1) {
			$num_args = func_num_args();
			for ($i = 2; $i < $num_args; $i++) {
				$var = func_get_arg($i);
				$arr = explode("=", $var, 2);
				if (count($arr) == 2) {
					$qs_arr[$arr[0]] = $arr[1];
				}
			}
		}
		
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
			case "pingback":   return $dir_uri.ENTRY_PINGBACK_DIR."/";
			case "upload":      return $dir_uri."uploadfile.php";
			case "edit":
				$entry_type = is_a($this, 'Article') ? 'article' : 'entry';
				$qs_arr['blog'] = $this->parentID();
				$qs_arr[$entry_type] = $this->entryID();
				return make_uri(INSTALL_ROOT_URL."pages/editentry.php", 
				                array("blog"     =>$this->parentID(), 
				                      $entry_type=>$this->entryID()));
			case "delete":     
				if (KEEP_EDIT_HISTORY) {
					return $dir_uri."delete.php";
				} else {
					$entry_type = is_a($this, 'Article') ? 'article' : 'entry';
					$qs_arr['blog'] = $this->parentID();
					$qs_arr[$entry_type] = $this->entryID();
					return make_uri(INSTALL_ROOT_URL."pages/delentry.php", $qs_arr);
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
			create_directory_wrappers($dir_path.PATH_DELIM.ENTRY_COMMENT_DIR, ENTRY_COMMENTS, get_class($this));
		}
		if ($this->allow_tb &&
		    ! is_dir($dir_path.PATH_DELIM.ENTRY_TRACKBACK_DIR) ) {
			create_directory_wrappers($dir_path.PATH_DELIM.ENTRY_TRACKBACK_DIR, ENTRY_TRACKBACKS, get_class($this));
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
		
		# If the entry directory already exists, something is wrong. 
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
		create_directory_wrappers($dir_path.PATH_DELIM.ENTRY_PINGBACK_DIR, ENTRY_PINGBACKS);
				
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
		$this->allow_pingback = POST("pingbacks") ? 1 : 0;
		$this->has_html = POST("input_mode");
		$this->enclosure = POST("enclosure");
		foreach ($this->custom_fields as $fld=>$desc) {
			$this->$fld = POST($fld);
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
		$tmp->set("ENCLOSURE", $this->enclosure);
		$tmp->set("ENCLOSURE_DATA", $this->getEnclosure());
		$tmp->set("ALLOW_COMMENTS", $this->allow_comment);
		$tmp->set("ALLOW_TRACKBACKS", $this->allow_tb);
		$tmp->set("ALLOW_PINGBACKS", $this->allow_pingback);
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
		$tmp->set("PINGBACKCOUNT", $this->getPingbackCount() );
		$tmp->set("PINGBACK_LINK", $this->uri('pingback'));
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

	# Generic reply-handling functions. 
	# These functions are generic and can get lists of comments, trackbacks, or 
	# pingbacks based on given parameters.  The public methods for getting the
	# different kinds of replies are wrappers around these.
	
	# Method: getReplyCount
	# Get the number of replies of a particular type.
	#
	# Parameters:
	# parms - An associative array of various parameters.  The valid array
	#         keys are "path", which is the directory to scan, "ext", which is
	#         the file extension of the data files, and "match_re", which is a 
	#         regular expression matching the names of the data files minus
	#         the extension (default is /[\w\d]+/)).
	#         Note that "path" and "ext" keys are required.
	#
	# Returns:
	# An integer representing the number of replies of the given type.
	# If the call fails for some reason, then false is returned.
	
	function getReplyCount($params) {
		$dir_path = dirname($this->file);
		$dir_path = $dir_path.PATH_DELIM.$params['path'];
		$dir_array = scan_directory($dir_path);
		if ($dir_array === false) return false;
		
		$count = 0;
		foreach ($dir_array as $file) {
			$cond = is_file($dir_path.PATH_DELIM.$file) && 
			        preg_match("/[\w\d]+".$params['ext']."/", $file);
			if ($cond) $count++;
		}
		return $count;
	}
	
	# Method: getReplyArray
	# Get an array of replies of a particular type.
	#
	# Parameters:
	# params - An associative array of settings, as in <getReplyCount>.  In 
	#          *addition* to the keys allowed by that function, this one also 
	#          requires a "creator" key, which is the name of a function that
	#          will return the correct type of object given the data path to 
	#          its storage file as a parameter.  It also accepts an optional
	#          "sort_asc" key, which will sort the files in order by filename,
	#          (which equates to date order) rather than in reverse order when 
	#          set to true.
	#
	# Returns:
	# An array of BlogComment, Trackback, or Pingback objects, depending on 
	# the parameters.  Returns false on failure.
	
	function getReplyArray($params) {
		$dir_path = dirname($this->file);
		$dir_path = $dir_path.PATH_DELIM.$params['path'];
		if (! is_dir($dir_path)) return false;
		else $reply_dir = scan_directory($dir_path);
		
		$reply_files = array();
		foreach ($reply_dir as $file) {
			$cond = is_file($dir_path.PATH_DELIM.$file) && 
			        preg_match("/[\w\d]+".$params['ext']."/", $file);
			if ($cond) $reply_files[] = $file; 
		}
		if (isset($params['sort_asc']) && $params['sort_asc']) {
			sort($reply_files);
		} else {
			rsort($reply_files);
		}

		$reply_array = array();
		$creator = $params['creator'];
		foreach ($reply_files as $file)
			$reply_array[] = $creator($dir_path.PATH_DELIM.$file);
		
		return $reply_array;
	}
	
	# Method: getReplies
	# Gets the HTML markup to display to list a given type of reply.
	#
	# Parameters:
	# params - An associative array of parameters, as in <getReplyArray>.  In 
	#          addition, it requires the 'itemclass' and 'listtitle' keys to be
	#          set.  These are the CSS class applied to each reply and the title
	#          given to the whole reply list, respectively.  There is also an 
	#          optional "listclass" key that gives the CSS class to apply to 
	#          the list.
	#
	# Returns:
	# A string of HTML markup to send to the client.
	
	function getReplies($params) {
		$replies = $this->getReplyArray($params);
		$ret = "";
		if ($replies) {
			$reply_text = array();
			foreach ($replies as $reply) {
				$reply_text[] = $reply->get();
			}
		}

		# Suppress markup entirely if there are no replies of the given type.
		if (isset($reply_text)) {
			$tpl = NewTemplate(LIST_TEMPLATE);
			$tpl->set("ITEM_CLASS", $params['itemclass']);
			if (isset($params['listclass'])) {
				$tpl->set("LIST_CLASS", $params['listclass']);
			}
			$tpl->set("ORDERED");
			$tpl->set("LIST_TITLE", $params['listtitle']);
			$tpl->set("ITEM_LIST", $reply_text);
			$ret = $tpl->process();
		}

		return $ret;
	}
	
	# Comment handling functions.

	/*
	Method: getCommentCount
	Determine the number of comments that belong to this object.

	Returns:
	A non-negative integer representing the number of comments or false on 
	failure.
	*/
	function getCommentCount() {
		$params = array('path'=>ENTRY_COMMENT_DIR, 'ext'=>COMMENT_PATH_SUFFIX);
		return $this->getReplyCount($params);
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
		$params = array('path'=>ENTRY_COMMENT_DIR, 'ext'=>COMMENT_PATH_SUFFIX,
		                'creator'=>'NewBlogComment', 'sort_asc'=>$sort_asc);
		return $this->getReplyArray($params);
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
		$title = spf_('Comments on <a href="%s">%s</a>', 
		              $this->permalink(), $this->subject);
		$params = array('path'=>ENTRY_COMMENT_DIR, 'ext'=>COMMENT_PATH_SUFFIX,
		                'creator'=>'NewBlogComment', 'sort_asc'=>$sort_asc,
		                'itemclass'=>'fullcomment', 'listtitle'=>$title);
		return $this->getReplies($params);
	}

	# TrackBack handling functions.

	/*
	Method: getTrackbackCount
	Get the number of TrackBacks for this object.

	Returns:
	A non-negative integer representing the number of TrackBacks or false on 
	failure.
	*/
	function getTrackbackCount() {
		$params = array('path'=>ENTRY_TRACKBACK_DIR, 'ext'=>TRACKBACK_PATH_SUFFIX);
		return $this->getReplyCount($params);
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
		$params = array('path'=>ENTRY_TRACKBACK_DIR, 
		                'ext'=>TRACKBACK_PATH_SUFFIX,
		                'creator'=>'NewTrackback', 'sort_asc'=>$sort_asc);
		return $this->getReplyArray($params);
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
		$title = spf_('Trackbacks on <a href="%s">%s</a>', 
		              $this->permalink(), $this->subject);
		$params = array('path'=>ENTRY_TRACKBACK_DIR, 'ext'=>TRACKBACK_PATH_SUFFIX,
		                'creator'=>'NewTrackback', 'sort_asc'=>$sort_asc,
		                'itemclass'=>'trackback', 'listclass'=>'tblist',
		                'listtitle'=>$title);
		return $this->getReplies($params);
	}
	
	# Pingback handling functions
	
	/*
	Method: getPingbackCount
	Get the number of Pingbacks for this object.

	Returns:
	A non-negative integer representing the number of Pingbacks or false on 
	failure.
	*/
	function getPingbackCount() {
		$params = array('path'=>ENTRY_PINGBACK_DIR, 'ext'=>PINGBACK_PATH_SUFFIX);
		return $this->getReplyCount($params);
	}

	/*
	Method: getPingbackArray
	Get an array of Pingback objects that contains all Pingbacks for 
	this entry.

	Parameters:
	sort_asc - *Optional* boolean (true by default) determining whether the
	           pingbacks should be sorted in ascending order by date.
	
	Returns:
	An array of Pingback objects.
	*/
	function getPingbackArray($sort_asc=true) {
		$params = array('path'=>ENTRY_PINGBACK_DIR, 
		                'ext'=>PINGBACK_PATH_SUFFIX,
		                'creator'=>'NewPingback', 'sort_asc'=>$sort_asc);
		return $this->getReplyArray($params);
	}

	/*
	Method: getPingbacks
	Get some HTML that displays the Pingbacks for this entry.

	Parameters:
	sort_asc - *Optional* boolean (true by default) determining whether the
	           pingbacks should be sorted in ascending order by date.
	
	Returns:
	A string of markup.
	*/				  
	function getPingbacks($sort_asc=true) {
		$title = spf_('Pingbacks on <a href="%s">%s</a>', 
		              $this->permalink(), $this->subject);
		$params = array('path'=>ENTRY_PINGBACK_DIR, 'ext'=>PINGBACK_PATH_SUFFIX,
		                'creator'=>'NewPingback', 'sort_asc'=>$sort_asc,
		                'itemclass'=>'pingback', 'listclass'=>'pblist',
		                'listtitle'=>$title);
		return $this->getReplies($params);
	}
	
	# Method: sendPings
	# Scans the links in the current entry, determines which are 
	# pingback-enabled, and sends a pingback ping to those links.
	#
	# Parameters:
	# local - *Optional* boolean that determines whether or not to send pings
	#         to posts on the same webserver.  *Defaults* to false.
	# Returns:
	# An array of associative arrays.  Each array has 'uri' and a 'response' 
	# key, which contain the target URI and the XML-RPC response object.
	
	function sendPings($local=false) {
		
		$urls = $this->extractLinks($local);

		$ping = NewPingback();
		$ret = array();

		foreach ($urls as $uri) {
			
			$pb_server = $ping->checkPingbackEnabled($uri);

			if ($pb_server) {
			
				$linkdata = parse_url($pb_server);

				$host = isset($linkdata['host']) ? $linkdata['host'] : SERVER("SERVER_NAME");
				$path = isset($linkdata['path']) ? $linkdata['path'] : '';
				$port = isset($linkdata['port']) ? $linkdata['port'] : 80;
				
				$parms = array(new xmlrpcval($this->permalink(), 'string'), 
				               new xmlrpcval($uri, 'string'));
				$msg = new xmlrpcmsg('pingback.ping', $parms);
				
				$client = new xmlrpc_client($path, $host, $port);
				#$client->setDebug(1);
				$result = $client->send($msg);
				$ret[] = array('uri'=>$uri, 'response'=>$result);
			}
			
		}
		return $ret;
	}
	
	# Method: pingExists
	# Checks if a Pingback ping has already been recorded for the source URL.
	#
	# Parameters:
	# uri - The source URI to check.
	#
	# Returns:
	# True if there is already a recorded ping with the source URI, false 
	# otherwise.
	
	function pingExists ($uri) {
		$pings = $this->getPingbackArray();
		if (! $pings) return false;
		foreach ($pings as $p) {
			if ($p->source == $uri) return true;
		}
		return false;
	}
	
	# Method: extractLinks
	# Extracts all the hyperlinks from the entry text.
	#
	# Parameters:
	# allow_local - *Optional* boolean parameter that determines if local links,
	#               should be included.  *Defaults* to false.
	#
	# Returns:
	# An array of URLs containing each hyperlink in the entry.  If allow_local 
	# is false, then links without a protocol and host name are excluded.
	
	function extractLinks($allow_local=false) {
		$matches = array();
		$data = $this->markup();

		$ret = preg_match_all('/href="([^"]+)"/i', $data, $matches);

		$url_matches = $matches[1];  # Grab only the saved subexpression.
		$ret = array();

		foreach ($url_matches as $m) {
			if ($allow_local) {
				$ret[] = $m;
			} else {
				# If we're NOT allowing local pings, filter them out.
				$url = parse_url($m);
				if (isset($url['host']) && $url['host'] != SERVER("SERVER_NAME")) {
					$ret[] = $m;
				}
			}
		}

		return $ret;
	}

}
?>
