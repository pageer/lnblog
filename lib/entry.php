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

require_once("utils.php");

class Entry {

	var $id;
	var $uid;
	var $ip;
	var $date;
	var $timestamp;
	var $subject;
	var $abstract;
	var $data;
	var $file;
	var $has_html;

	#var $store_path = CFG_ENTRY_DIR;

	function stripHTML($data) {
		$ret = htmlentities($data);
		return $ret;
	}

	function addHTML($data) {
		$ret = $data;
		$ret = preg_replace("/((http|https|ftp):\/\/\S*)/i", "<a href=\"\$1\">\$1</a>", $ret);
		$ret = "<p>".$ret."</p>";
		$ret = preg_replace('/\r\n\r\n/', '</p><p>', $ret);
		$ret = preg_replace('/\n\n/', '</p><p>', $ret);
		$ret = preg_replace('/\n/', '<br />', $ret);
		return $ret;
	}

	function bbcodeToHTML($data) {
		$ret = $data;
		$ret = preg_replace("/\[url=(\S+)\](.+)\[\/url\]/Ui", '<a href="$1">$2</a>', $ret);
		$ret = preg_replace("/\[b\](.+)\[\/b\]/Ui", '<strong>$1</strong>', $ret);
		$ret = preg_replace("/\[i\](.+)\[\/i\]/Ui", '<em>$1</em>', $ret);
		$ret = "<p>".$ret."</p>";
		$ret = preg_replace('/\r\n\r\n/', '</p><p>', $ret);
		$ret = preg_replace('/\n\n/', '</p><p>', $ret);
		$ret = preg_replace('/\n/', '<br />', $ret);
		return $ret;
	}

	# A quick function to get an abstract by grabbing the first N characters.

	function getAbstract($numchars=500) {
		$ret = substr($this->data, 0, $numchars);
		if (strlen($ret) == $numchars) $ret .= "...";
		return $ret;
	}

	function prettyDate() {
		# This is a hack.  We remove any fractional seconds (which Sybase 
		# includes in datetime fields) because it confuses strtotime.
		# We also convert full time zones to abbreviations, e.g. we change
		# "Eastern Standard Time" to "EST", because Windows likes to output
		# the spelled-out version, but strtotime() doesn't like it.
		#echo "<p>$this->date</p>";
		#$clean_date = preg_replace("/\.\d+/", "", $this->date);
		#$clean_date = preg_replace("/[A-Za-z] ?/", "", $clean_date);
		#echo "<p>$clean_date</p>";
		
		#$date_ts = strtotime($clean_date);
		$date_ts = $this->timestamp;
		#echo "<p>".$this->date.", ".$date_ts.", ".$clean_date."</p>";
		$print_date = date(ENTRY_DATE_FORMAT, $date_ts);
		#echo "<p>$print_date</p>";
		return $print_date;
	}

	function univDate($ts=false) {
		if ($ts) return date(CFG_DB_DATE_FMT, $ts);
		else     return date(CFG_DB_DATE_FMT);
	}
	
	# Add metadata from the file to the object.  Unknown metadata will be 
	# ignored, while recognized data will be set to class properties.
	
	function addMetadata($key, $val) {
		switch ($key) {
			case "PostID":  $this->id = $val; break;
			case "UserID":  $this->uid = $val; break;
			case "Date":    $this->date = $val; break;
			case "IP":      $this->ip = $val; break;
			case "Subject": $this->subject = $val; break;
			case "HasHTML": $this->has_html = $val; break;
		}
	}
	
	function generateFilePath($ts) {
		$base = date(CFG_STORE_DATE_FMT, $ts) . "-" . $this->uid;
		$ret = $base . ".htm";
		$i = 0;
		while ( file_exists($ret) ) {
			$ret = $base . "-" . $i . ".htm";
			$i =+ 1;
		}
		return $this->store_path . "/" . $ret;
	}

	function readFileData() {
		#$data = file($this->store_path . "/" . $this->datafile);
		$data = file($this->file);
		$file_data = "";
		if (! $data) $file_data = false;
		else 
			foreach ($data as $line) {
				preg_match('/<!--META (.*): (.*) META-->/', $line, $matches);
				if ($matches) $this->addMetadata($matches[1], $matches[2]);
				$cleanline = preg_replace("/<!--META.*META-->\s/", "", $line);
				#if (preg_match("/\S/", $cleanline) == 0) $cleanline = "";
				
				$file_data .= $cleanline;
			}
		if (get_magic_quotes_gpc() ) $file_data = stripslashes($file_data);
		$this->abstract = $this->getAbstract();
		$this->data = $file_data;
		return $file_data;
	}
	
	function metadataFields() {
		$ret = array();
		$ret["PostID"] = $this->id;
		$ret["UserID"] = $this->uid;
		$ret["Date"] = $this->date;
		$ret["IP"] = $this->ip;
		$ret["Subject"] = $this->subject;
		$ret["HasHTML"] = $this->has_html ? 1 : 0;
		return $ret;
	}

	function putMetadataFields() {
		$fields = $this->metadataFields();
		$ret = "";
		foreach ($fields as $key=>$val) 
			$ret .= "<!--META ".$key.": ".$val." META-->\n";
		return $ret;
	}

	function writeFileData($file_data="") {
		$header = $this->putMetadataFields();
		$file_data = $header.$this->data;
		if (! is_dir(dirname($this->file)) ) 
			mkdir_rec(dirname($this->file), 0777); 
		$ret = write_file($this->file, $file_data);
		return $ret;
	}

	function getBlogBasedir() {
		# Under the current design, we should never end up more than 5 levels 
		# beneath the blog root.  Comments will be 5 levels, blog entries will
		# be 4, month archives will be 3, year archives 2, and nothing else
		# should be more than one.
		$curr_dir = getcwd();
		for ($i=0; $i<=5; $i++) {
			# Look for the blog metadata file.
			$path = $curr_dir.PATH_DELIM.BLOG_CONFIG_PATH;
			if ( file_exists($path) ) return $curr_dir;
			$curr_dir = dirname($curr_dir);
		}
		return false;
	}

	function getTemplatePath($file=false) {
		$curr_dir = getcwd();
		for ($i=0; $i<=5; $i++) {
			if ($file) {
				$path = $curr_dir.PATH_DELIM.BLOG_TEMPLATE_DIR.PATH_DELIM.$file;
				if ( file_exists($path) ) return $path;
			} else {
				$path = $curr_dir.PATH_DELIM.BLOG_TEMPLATE_DIR;
				if ( is_dir($path) ) return $path;
			}
			$curr_dir = dirname($curr_dir);
		}
		return false;
	}

}

?>
