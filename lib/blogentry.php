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
	
	var $blogid;
	var $allow_comment = true;
	var $has_html;
	var $mail_notify = true;
	var $sent_ping = true;
	var $abstract;

	function BlogEntry ($path="", $revision=ENTRY_DEFAULT_FILE) {
		
		$this->raiseEvent("OnInit");
	
		$this->uid = ADMIN_USER;
		$this->ip = get_ip();
		$this->date = "";
		$this->post_date = false;
		$this->timestamp = "";
		$this->post_ts = false;
		$this->mail_notify = true;
		$this->sent_ping = true;
		$this->subject = "";
		$this->data = "";
		$this->abstract = "";
		$this->has_html = MARKUP_BBCODE;
		$this->allow_comment = true;
		$this->template_file = ENTRY_TEMPLATE;
		
		# Auto-detect the current entry.  If no path is given, 
		# then assume the current directory.
		if (! $path) {
			$this->file = getcwd().PATH_DELIM.$revision;
			# We might be in a comment or trackback directory, 
			if (! $this->isEntry() ) {
				$this->file = dirname(getcwd()).PATH_DELIM.$revision;
				if (! $this->isEntry() ) {
					$this->file = getcwd().PATH_DELIM.$revision;
				}
			}
		} else {
			$this->file = $path.PATH_DELIM.$revision;
		}
		
		if ( file_exists($this->file) ) $this->readFileData();
		
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
		if (! defined("BLOG_ROOT")) return false;
		return NewBlog();
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
		#if ($just_name || $long_format) {
			$fmt = $long_format ? ENTRY_PATH_FORMAT_LONG : ENTRY_PATH_FORMAT;
			$base = date($fmt, $curr_ts);
		/*
		} else {
			$base = strtolower($this->subject);
			$base = preg_replace("/\s+/", "_",$base);
			$base = date("d", $curr_ts)."-".$base;
		}
		*/
		if ($just_name) return $base;
		else return $year.PATH_DELIM.$month.PATH_DELIM.$base;
	}

	function metadataFields() {
		$ret = array();
		#$ret["PostID"] =  $this->id;
		$ret["UserID"] =  $this->uid;
		$ret["Date"] =  $this->date;
		$ret["PostDate"] = $this->post_date;
		$ret["Timestamp"] =  $this->timestamp;
		$ret["PostTimestamp"] = $this->post_ts;
		$ret["IP"] =  $this->ip;
		$ret["Mail Notification"] = $this->mail_notify;
		$ret["TrackBack Ping"] = $this->sent_ping;
		$ret["Subject"] =  $this->subject;
		$ret["Abstract"] = $this->abstract;
		$ret["AllowComment"] =  $this->allow_comment;
		$ret["HasHTML"] = $this->has_html;
		return $ret;
	}
	
	# Note that for some fields, the assignment is conditional.
	# This is to prevent default values from being over-written by empty ones.
	
	function addMetadata($key, $val) {
		switch ($key) {
			case "PostID": $this->id = $val; break;
			case "UserID": if ($val) $this->uid = $val; break;
			case "Date": $this->date = $val; break;
			case "PostDate": $this->post_date = $val; break;
			case "Timestamp": $this->timestamp = $val; break;
			case "PostTimestamp": $this->post_ts = $val; break;
			case "IP": $this->ip = $val; break;
			case "Mail Notification": $this->mail_notify = $val; break;
			case "TrackBack Ping": $this->sent_ping = $val; break;
			case "Subject": $this->subject = $val; break;
			case "Abstract": $this->abstract = $val; break;
			case "AllowComment": $this->allow_comment = $val; break;
			case "HasHTML": $this->has_html = $val; break;
		}
	}
	
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
		return localpath_to_uri($this->localpath());
	}
	
	/*
	Method: commentlink
	Gets the URI of the comments page.
	
	Returns:
	A string holding the permalink to the comments for this entry.
	*/
	function commentlink() {
		return localpath_to_uri($this->localpath().PATH_DELIM.ENTRY_COMMENT_DIR);
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
		
		$fs = NewFS();
		$dir_path = dirname($this->file);
		$this->ip = get_ip();
		$curr_ts = time();
		$this->date = date(ENTRY_DATE_FORMAT, $curr_ts);
		$this->timestamp = $curr_ts;

		$target = $dir_path.PATH_DELIM.
			$this->getPath($curr_ts, true, true).ENTRY_PATH_SUFFIX;
		$source = $dir_path.PATH_DELIM.ENTRY_DEFAULT_FILE;
		$ret = $fs->rename($source, $target);
		$fs->destruct();
		if ($ret) $ret = $this->writeFileData();
		$this->raiseEvent("UpdateComplete");
		return $ret;
	}

	/* 
	Method: delete
	Delete the current object

	Returns:
	True on success, false on failure.
	*/
	function delete () {
		
		$fs = NewFS();
		$curr_ts = time();
		$dir_path = dirname($this->file);
		if (! $this->isEntry($dir_path) ) return false;
		$this->raiseEvent("OnDelete");
		$source_file = $dir_path.PATH_DELIM.ENTRY_DEFAULT_FILE;
		$target_file = $dir_path.PATH_DELIM.
			$this->getPath($curr_ts, true, true).ENTRY_PATH_SUFFIX;
		$ret = $fs->rename($source_file, $target_file);
		$fs->destruct();
		$this->raiseEvent("DeleteComplete");
		return $ret;
	}

	/*
	Method: insert
	Save the object to persistent storage.

	Returns:
	True on success, false on failure.
	*/
	function insert ($base_path=false) {
	
		$usr = NewUser();
		if (! $usr->checkLogin() ) return false;
		$this->uid = $usr->username();
	
		$curr_ts = time();
		if (!$base_path) $basepath = getcwd().PATH_DELIM.BLOG_ENTRY_PATH;
		else $basepath = $base_path;
		$dir_path = $basepath.PATH_DELIM.$this->getPath($curr_ts);
		# If the entry driectory already exists, something is wrong. 
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
		$this->date = date(ENTRY_DATE_FORMAT, $curr_ts);
		if (! $this->post_date) 
			$this->post_date = date(ENTRY_DATE_FORMAT, $curr_ts);
		$this->timestamp = $curr_ts;
		if (! $this->post_ts) $this->post_ts = $curr_ts;
		$this->ip = get_ip();

		$ret = $this->writeFileData();
		$this->raiseEvent("InsertComplete");
		return $ret;
		
	}

	/*
	Method: getPostData
	Extract data from an HTTP POST and insert it into the object.
	*/
	function getPostData() {
		$this->subject = POST(ENTRY_POST_SUBJECT);
		$this->abstract = POST(ENTRY_POST_ABSTRACT);
		$this->data = POST(ENTRY_POST_DATA);
		$this->allow_comment = POST(ENTRY_POST_COMMENTS) ? 1 : 0;
		$this->has_html = POST(ENTRY_POST_HTML);
		if (get_magic_quotes_gpc()) {
			$this->subject = stripslashes($this->subject);
			$this->data = stripslashes($this->data);
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
	
		$tmp = NewTemplate(ENTRY_TEMPLATE);
		
		$usr = NewUser($this->uid);
		$usr->exportVars($tmp);
		
		$tmp->set("SUBJECT", $this->subject);
		$tmp->set("POSTDATE", $this->prettyDate($this->post_ts) );
		$tmp->set("EDITDATE", $this->prettyDate() );
		$tmp->set("ABSTRACT", $this->abstract);
		$tmp->set("BODY", $this->markup() );
		$tmp->set("ALLOW_COMMENTS", $this->allow_comment);
		$tmp->set("PERMALINK", $this->permalink() );
		$tmp->set("COMMENTCOUNT", $this->getCommentCount() );
		$tmp->set("TRACKBACKCOUNT", $this->getTrackbackCount() );
		$tmp->set("SHOW_CONTROLS", $show_edit_controls);
		
		$ret .= $tmp->process();
		ob_start();
		$this->raiseEvent("OutputComplete");
		$ret .= ob_get_contents();
		ob_end_clean();
		return $ret;
	}

	# Comment handling functions.

	/*
	Method: addComment
	Insert a new comment based on HTTP POST data.

	Parameters:
	path - *Optional* path to the comments for this object.  By default,
	       this will be auto-detected.
	
	Returns:
	True on success, false on failure.
	*/
	function addComment($path=false) {
		if (! $this->allow_comment) return false;
		if (! $path) $path = $this->localpath().PATH_DELIM.ENTRY_COMMENT_DIR;
		$cmt = NewBlogComment();
		$cmt->getPostData();
		if ($cmt->data) {
			$ret = $cmt->insert($path);
		}
		else $ret = false;
		return $ret;
	}

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
			foreach ($comment_files as $file) {
				$ret .= $file->get();
			}
		}

		# Suppress the comment stuff entirely for posts that don't have 
		# comments and/or don't allow them.
		if ($ret || $this->allow_comment) {
			$tpl = NewTemplate(COMMENT_LIST_TEMPLATE);
			$tpl->set("ENTRY_PERMALINK", $this->permalink() );
			$tpl->set("ENTRY_SUBJECT", $this->subject);
			$tpl->set("COMMENT_LIST", $ret);
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
		foreach ($trackbacks as $tb) {
			$ret .= $tb->get();
		}
		
		$tpl = NewTemplate(TRACKBACK_LIST_TEMPLATE);
		$tpl->set("ENTRY_PERMALINK", $this->permalink() );
		$tpl->set("ENTRY_SUBJECT", $this->subject);
		$tpl->set("TB_LIST", $ret);
		$ret = $tpl->process();
		
		return $ret;
	}

	/*
	Method: getPing
	Recieves HTTP POST data containing a TrackBack ping and saves it in 
	persistent storage.

	Returns:
	Zero on success, one on failure.
	*/
	function getPing() {
		$tb = NewTrackback();
		$ret = $tb->receive(dirname($this->file).PATH_DELIM.ENTRY_TRACKBACK_DIR);
		return $ret;
	}

	/*
	Method: sendPing
	Send a TrackBack ping for the current entry to another blog.

	Parameters:
	url     - The TrackBack ping to which to post data.
	excerpt - *Optional* excerpt of the blog entry text to send. 
	          No excerpt is sent by default.

	Returns:
	The return code sent by the remote server.  Normally this is 0 on success
	and a non-zero value on failure.
	*/
	function sendPing($url, $excerpt='') {
		$tb = NewTrackback();
		$tb->title = $this->subject;
		$tb->blog = BLOG_ROOT_URL;
		$tb->url = $this->permalink();
		$tb->data = $excerpt;
		return $tb->send($url);
	}

}
?>
