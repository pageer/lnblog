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
#require_once("blogcomment.php");



class BlogEntry extends Entry {
	
	var $blogid;
	var $allow_comment = true;
	var $has_html;
	var $abstract;

	function BlogEntry ($path="", $revision=ENTRY_DEFAULT_FILE) {
		#var $uid;
		$this->ip = get_ip();
		$this->date = "";
		$this->post_date = false;
		$this->timestamp = "";
		$this->post_ts = false;
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
		#$ret["UserID"] =  $this->uid;
		$ret["Date"] =  $this->date;
		$ret["PostDate"] = $this->post_date;
		$ret["Timestamp"] =  $this->timestamp;
		$ret["PostTimestamp"] = $this->post_ts;
		$ret["IP"] =  $this->ip;
		$ret["Subject"] =  $this->subject;
		$ret["Abstract"] = $this->abstract;
		$ret["AllowComment"] =  $this->allow_comment;
		$ret["HasHTML"] = $this->has_html;
		return $ret;
	}
	
	function addMetadata($key, $val) {
		switch ($key) {
			case "PostID": $this->id = $val; break;
			case "UserID": $this->uid = $val; break;
			case "Date": $this->date = $val; break;
			case "PostDate": $this->post_date = $val; break;
			case "Timestamp": $this->timestamp = $val; break;
			case "PostTimestamp": $this->post_ts = $val; break;
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

	function commentlink() {
		return localpath_to_uri(dirname($this->file).PATH_DELIM.ENTRY_COMMENT_DIR);
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
		
		$fs = CreateFS();
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
		if (! $ret) return false;
		$ret = $this->writeFileData();
		return $ret;
	}

	function delete () {
		
		if (! check_login()) return false;
		
		$fs = CreateFS();
		$curr_ts = time();
		$dir_path = dirname($this->file);
		if (! $this->isEntry($dir_path) ) return false;
		$source_file = $dir_path.PATH_DELIM.ENTRY_DEFAULT_FILE;
		$target_file = $dir_path.PATH_DELIM.
			$this->getPath($curr_ts, true, true).ENTRY_PATH_SUFFIX;
		$ret = $fs->rename($source_file, $target_file);
		$fs->destruct();
		return $ret;
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
		# Set the timestamp and date, plus the ones for the original post, if
		# this is a new entry.
		$this->date = date(ENTRY_DATE_FORMAT, $curr_ts);
		if (! $this->post_date) 
			$this->post_date = date(ENTRY_DATE_FORMAT, $curr_ts);
		$this->timestamp = $curr_ts;
		if (! $this->post_ts) $this->post_ts = $curr_ts;
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

	function get() {
		$tmp = new PHPTemplate(ENTRY_TEMPLATE);

		$tmp->set("SUBJECT", $this->subject);
		$tmp->set("POSTDATE", $this->prettyDate($this->post_ts) );
		$tmp->set("EDITDATE", $this->prettyDate() );
		if ($this->has_html == MARKUP_BBCODE)
			$this->data = $this->absolutizeBBCodeURI($this->data, $this->permalink() );
		$this->data = $this->markup($this->data() );
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

	function updateRSS1() {
		$feed = new RSS1;
		$comment_path = dirname($this->file).PATH_DELIM.ENTRY_COMMENT_DIR;
		$path = $comment_path.PATH_DELIM.COMMENT_RSS1_PATH;
		$feed_url = localpath_to_uri($path);

		$feed->url = localpath_to_uri($path);;
		#$feed->image = $this->image;
		$feed->title = $this->subject;
		$feed->description = $this->subject;
		$feed->site = BLOG_ROOT_URL;
	
		$comm_list = $this->getCommentArray();
		foreach ($comm_list as $ent) 
			$feed->entrylist[] = new RSS1Entry($ent->permalink(), $ent->subject, $ent->subject);
		
		$ret = $feed->writeFile($path);	
		return $ret;
	}

	function updateRSS2() {
		$feed = new RSS2;
		$comment_path = dirname($this->file).PATH_DELIM.ENTRY_COMMENT_DIR;
		$path = $comment_path.PATH_DELIM.COMMENT_RSS2_PATH;
		$feed_url = localpath_to_uri($path);

		$feed->url = $feed_url;
		#$feed->image = $this->image;
		$feed->description = $this->subject;
		$feed->title = $this->subject;
	
		$comm_list = $this->getCommentArray();
		foreach ($comm_list as $ent) 
			$feed->entrylist[] = new RSS2Entry($ent->permalink(), 
				$ent->subject, 
				"<![CDATA[".$ent->markup($ent->data)."]]>");
		
		$ret = $feed->writeFile($path);	
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
			$comment_array[] = new BlogComment($comment_dir_path.PATH_DELIM.$file);
		
		return $comment_array;
	}

	function getComments($sort_asc=true) {
		$comment_files = $this->getCommentArray($sort_asc);	
		if (!$comment_files) return "";
		$ret = "";
		foreach ($comment_files as $file) {
			$ret .= $file->get();
		}
		return $ret;
	}

}

?>
