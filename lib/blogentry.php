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
require_once("utils.php");
require_once("template.php");
require_once("entry.php");
require_once("blogcomment.php");

define("MARKUP_NONE", 0);
define("MARKUP_BBCODE", 1);
define("MARKUP_HTML", 2);

class BlogEntry extends Entry {
	
	var $blogid;
	var $allow_comment = true;
	var $has_html;
	var $abstract;

	function BlogEntry ($path="", $revision=ENTRY_DEFAULT_FILE) {
		#var $uid;
		$this->ip = get_ip();
		$this->date = "";
		$this->timestamp = "";
		$this->subject = "";
		$this->data = "";
		$this->abstract = "";
		$this->has_html = MARKUP_BBCODE;
		$this->file = $path . ($path ? PATH_DELIM.$revision : "");
		$this->allow_comment = true;
		$this->template_file = ENTRY_TEMPLATE;
		if ( file_exists($this->file) )$this->readFileData();
	}
	
	# Returns a path to use for the 

	function getPath($curr_ts, $just_name=false, $long_format=false) {
		$year = date("Y", $curr_ts);
		$month = date("m", $curr_ts);
		$fmt = $long_format ? ENTRY_PATH_FORMAT_LONG : ENTRY_PATH_FORMAT;
		$base = date($fmt, $curr_ts);
		return $year.PATH_DELIM.$month.PATH_DELIM.$base;
	}

	function metadataFields() {
		$ret = array();
		#$ret["PostID"] =  $this->id;
		#$ret["UserID"] =  $this->uid;
		$ret["Date"] =  $this->date;
		$ret["Timestamp"] =  $this->timestamp;
		$ret["IP"] =  $this->ip;
		$ret["Subject"] =  $this->subject;
		$ret["Abstract"] = $this->abstract;
		$ret["AllowComment"] =  $this->allow_comment;
		$ret["HasHTML"] = $this->has_html;
		return $ret;
	}
	
	function addMetadata($key, $val) {
		switch ($key) {
			#case "PostID": $this->id = $val; break;
			#case "UserID": $this->uid = $val; break;
			case "Date": $this->date = $val; break;
			case "Timestamp": $this->timestamp = $val; break;
			case "IP": $this->ip = $val; break;
			case "Subject": $this->subject = $val; break;
			case "Abstract": $this->abstract = $val; break;
			case "AllowComment": $this->allow_comment = $val; break;
			case "HasHTML": $this->has_html = $val; break;
		}
	}
	
	function isEntry ($path) {
		return file_exists($path.PATH_DELIM.ENTRY_DEFAULT_FILE);
	}

	function permalink() {
		return localpath_to_uri(dirname($this->file));
	}
	
	function getByPath ($path, $revision=ENTRY_DEFAULT_FILE) {
		$file_path = $path.PATH_DELIM.$revision;
		return $this->readFileData($file_path); 
	}
	
	function validate() {
		$ret = preg_match("/^\d+$/", $this->id);
		$ret &= preg_match("/^\d+$/", $this->blogid);
		$ret &= (trim($this->uid) != "");
		$ret &= (trim($this->data) != "");
	}
	
	function update () {
		
		if (! check_login()) return false;

		$dir_path = dirname($this->file);
		$this->ip = get_ip();
		$curr_ts = time();
		$this->date = date(ENTRY_DATE_FORMAT, $curr_ts);
		$this->timestamp = $curr_ts;

		$target = $dir_path.PATH_DELIM.
			basename($this->getPath($curr_ts, false, true)).ENTRY_PATH_SUFFIX;
		$source = $dir_path.PATH_DELIM.ENTRY_DEFAULT_FILE;
		$ret = rename($source, $target);
		if (! $ret) return false;
		$ret = $this->writeFileData();
		return $ret;
	}

	function delete () {
		
		if (! check_login()) return false;
		
		$curr_ts = time();
		$dir_path = dirname($this->file);
		if (! $this->isEntry($dir_path) ) return false;
		$source_file = $dir_path.PATH_DELIM.ENTRY_DEFAULT_FILE;
		$target_file = $dir_path.PATH_DELIM.
			basename($this->getPath($curr_ts, false, true)).ENTRY_PATH_SUFFIX;
		return rename($source_file, $target_file);
	}

	function insert ($base_path=false) {
	
		if (! check_login()) return false;
	
		$curr_ts = time();
		if (!$base_path) $basepath = getcwd().PATH_DELIM.BLOG_ENTRY_PATH;
		else $basepath = $base_path;
		$dir_path = $basepath.PATH_DELIM.$this->getPath($curr_ts);

		# First, check that the year and month directories exist and have
		# the appropriate wrapper scripts in them.
		$month_path = dirname($dir_path);
		$year_path = dirname($month_path);
		if (! is_dir($year_path)) $ret = create_directory_wrappers($year_path, YEAR_ENTRIES);
		if (! is_dir($month_path)) $ret = create_directory_wrappers($month_path, MONTH_ENTRIES);

		# If the entry driectory already exists, something is wrong, so 
		# bail out.
		if ( is_dir($dir_path) ) return false;
		else $ret = create_directory_wrappers($dir_path, ENTRY_BASE);

		# Create the comments directory.
		create_directory_wrappers($dir_path.PATH_DELIM.ENTRY_COMMENT_DIR, ENTRY_COMMENTS);
				
		$this->file = $dir_path.PATH_DELIM.ENTRY_DEFAULT_FILE;
		$this->date = date(ENTRY_DATE_FORMAT, $curr_ts);
		$this->timestamp = $curr_ts;
		$this->ip = get_ip();
		
		if (get_magic_quotes_gpc() ) {
			$this->subject = stripslashes($this->subject);
			$this->data = stripslashes($this->data);
		}

		$ret = $this->writeFileData();
		return $ret;
		
	}

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
	}

	function markup($data) {
		switch ($this->has_html) {
			case MARKUP_NONE:
				$ret = $this->stripHTML($data);
				$ret = $this->addHTML($data);
				break;
			case MARKUP_BBCODE:
				$ret = $this->stripHTML($data);
				$ret = $this->bbcodeToHtml($data);
				break;
			case MARKUP_HTML:
				$ret = $data;
				break;
		}
		return $ret;
	}

	function get() {
		$tmp = new PHPTemplate(ENTRY_TEMPLATE);

		$tmp->set("SUBJECT", $this->subject);
		$tmp->set("POSTDATE", $this->prettyDate() );
		$this->data = $this->markup($this->data);
		$tmp->set("ABSTRACT", $this->abstract);
		$tmp->set("BODY", $this->data);
		$tmp->set("PERMALINK", $this->permalink() );
		$tmp->set("POSTEDIT", $this->permalink()."edit.php");
		$tmp->set("POSTDELETE", $this->permalink()."delete.php");
		$tmp->set("POSTCOMMENTS", $this->permalink().ENTRY_COMMENT_DIR);
		$tmp->set("COMMENTCOUNT", $this->getCommentCount() );
		
		$ret = $tmp->process();
		return $ret;
	}

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

	function getComments($sort_asc=true) {
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
		
		$ret = "";
		foreach ($comment_files as $file) {
			$curr_comment = new BlogComment($comment_dir_path.PATH_DELIM.$file);
			$ret .= $curr_comment->get();
		}
		return $ret;
	}

}

?>
