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

class BlogComment extends Entry {

	var $postid;
	var $name;
	var $email;
	var $url;
		
	function BlogComment ($path="", $revision="") {
		$this->ip = get_ip();
		$this->date = "";
		$this->timestamp = 0;
		$this->subject = "";
		$this->data = "";
		$this->file = $path . ($revision ? "_".$revision : "");
		$this->url = "";
		$this->email = "";
		$this->name = ANON_POST_NAME;
		if ( file_exists($this->file) ) $this->readFileData();
		#else echo "<p>Cannot find file: ".$this->file."</p>";
		else $this->file = "";
	}
		
	function getPath($ts) {
		$base = date(COMMENT_PATH_FORMAT, $ts);
		return $base;
	}
		
	function metadataFields() {
		$ret = array();
		$ret["PostID"] =  $this->id;
		$ret["Name"] =  $this->name;
		$ret["E-Mail"] =  $this->email;
		$ret["URL"] = $this->url;
		$ret["Date"] =  $this->date;
		$ret["Timestamp"] =  $this->timestamp;
		$ret["IP"] =  $this->ip;
		$ret["Subject"] =  $this->subject;
		return $ret;
	}

	function addMetadata($key, $val) {
		switch ($key) {
			case "PostID": $this->id = $val; break;
			case "Name": $this->name = $val; break;
			case "E-Mail": $this->email = $val; break;
			case "URL": $this->url = $val; break;
			case "Date": $this->date = $val; break;
			case "Timestamp": $this->timestamp = $val; break;
			case "IP": $this->ip = $val; break;
			case "Subject": $this->subject = $val; break;
		}
		#echo "<p>Meta: $key, $val</p>";
	}
		
	function update () {
		$ret = $this->delete();
		if ($ret) {
			$base_path = dirname($this->file); 
			$ret = $this->insert($base_path);
		}
		return $ret;
		
	}
	
	function delete () {
		if (! check_login() ) return false;
		$curr_ts = time();
		$dir_path = dirname($this->file);
		if (! is_dir($dir_path.PATH_DELIM.COMMENT_DELETED_PATH) )
			mkdir_rec($dir_path.PATH_DELIM.COMMENT_DELETED_PATH);
		$source_file = $this->file;
		$target_file = basename($this->file)."-".$this->getPath($curr_ts);
		$target_file = $dir_path.PATH_DELIM.COMMENT_DELETED_PATH.
			PATH_DELIM.$target_file;
		return rename($source_file, $target_file);
	}
	
	function insert($basepath) {
		$curr_ts = time();
	
		# Check if the file path is NULL so that we can re-use this routine
		# when updating an entry.
		if (! $this->file)
			$this->file = $basepath.PATH_DELIM.$this->getPath($curr_ts).COMMENT_PATH_SUFFIX;
		$this->date = date(ENTRY_DATE_FORMAT, $curr_ts);
		$this->timestamp = $curr_ts;
		$this->ip = get_ip();

		if (! is_dir($basepath) ) {
			$ret = create_directory_wrappers($basepath, ENTRY_COMMENTS);
			if (! $ret) return false;
		}
		
		# If there is no data for this comment, then abort.
		if (! $this->data) return false;
		
		if (get_magic_quotes_gpc() ) {
			$this->subject = stripslashes($this->subject);
			$this->data = stripslashes($this->data);
			$this->name = stripslashes($this->name);
			$this->url = stripslashes($this->url);
			$this->email = stripslashes($this->email);
		}

		$ret = $this->writeFileData();
		return $ret;
	}

	function getPostData() {
		$this->name = (POST(COMMENT_POST_NAME));
		$this->email = POST(COMMENT_POST_EMAIL);
		$this->url = POST(COMMENT_POST_URL);
		$this->subject = POST(COMMENT_POST_SUBJECT);
		$this->data = POST(COMMENT_POST_DATA);
		if (get_magic_quotes_gpc()) {
			$this->name = stripslashes($this->name);
			$this->email = stripslashes($this->email);
			$this->url = stripslashes($this->url);
			$this->subject = stripslashes($this->subject);
			$this->data = stripslashes($this->data);
		}
		$this->name = $this->stripHTML($this->name);
		$this->email = $this->stripHTML($this->email);
		$this->url = $this->stripHTML($this->url);
		$this->subject = $this->stripHTML($this->subject);
		$this->data = $this->stripHTML($this->data);
	}

	function getAnchor() {
		$ret = basename($this->file);
		$ret = preg_replace("/.\w\w\w$/", "", $ret);
		$ret = "comment".$ret;
		return $ret;
	}

	function getFilename($anchor) {
		$ret = substr($anchor, 7);
		$ret .= COMMENT_PATH_SUFFIX;
		$ret = realpath($ret);
		return $ret;
	}

	function get() {
		$t = new PHPTemplate(COMMENT_TEMPLATE);

		#$t->set("ID", $this->id);
		if (! $this->name) $this->name = ANON_POST_NAME;
		if (! $this->subject) $this->subject = NO_SUBJECT;
		$t->set("SUBJECT", $this->subject);
		$t->set("URL", $this->url);
		$t->set("NAME", $this->name);
		$t->set("DATE", $this->prettyDate() );
		$t->set("EMAIL", $this->email);
		$t->set("ANCHOR", $this->getAnchor() );
		if (! $this->has_html) $this->data = $this->addHTML($this->data);
		$t->set("BODY", $this->data);
		
		$ret = $t->process();
		return $ret;
	}
	
}

?>
