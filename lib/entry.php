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

define("MARKUP_NONE", 0);
define("MARKUP_BBCODE", 1);
define("MARKUP_HTML", 2);

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
	var $has_html = MARKUP_NONE;

	#var $store_path = CFG_ENTRY_DIR;

	function data($value=false) {
		if ($value) $this->data = $value;
		else return $this->data;
	}
	/*
	function data($value=false) {
		if ($value) $this->data = $value;
		else {
			$ret = $this->data;
			if ($this->has_html == MARKUP_BBCODE) 
				$ret = $this->absolutizeBBCodeURI($ret, 
						localpath_to_uri(dirname($this->file) ) );
			return $this->data;
		}
	}
	*/

	# A function to search BBCode marked-up text and convert the URIs in
	# links and images from relative to absolute.

	function absolutizeBBCodeURI($data, $current_uri) {
		$ret = preg_replace("/\[img=([^\/]+)\]/",
		                    "[img=".$current_uri."$1]", $data);
		$ret = preg_replace("/\[url=([^\/]+)\]/", 
		                    "[url=".$current_uri."$1]", $ret);
		return $ret;
	}

	function stripHTML($data) {
		$ret = htmlentities($data);
		return $ret;
	}

	function addHTML($data) {
		$ret = $data;
		$patterns[0] = "/((http|https|ftp):\/\/\S*)/i";
		$patterns[1] = '/\r\n\r\n/';
		$patterns[2] = '/\n\n/';
		$patterns[3] = '/\n/';
		$replacements[0] = "<a href=\"\$1\">\$1</a>";
		$replacements[1] = '</p><p>';
		$replacements[2] = '</p><p>';
		$replacements[3] = '<br />';
		ksort($patterns);
		ksort($replacements);
		$ret = preg_replace($patterns, $replacements, $ret);
		$ret = "<p>".$ret."</p>";
		return $ret;
	}

	function bbcodeToHTML($data) {
		$ret = $data;
		# Single-parameter tags.
		$patterns[0] = "/\[url=(\S+)\](.+)\[\/url\]/Ui";
		$patterns[1] = "/\[img=(.+)](.+)\[\/img\]/";
		$patterns[2] = "/\[ab=(.+)\](.+)\[\/ab\]/Ui";
		$patterns[3] = "/\[ac=(.+)\](.+)\[\/ac\]/Ui";
		$patterns[4] = "/\[quote\](.+)[\/quote]/Ui";
		$patterns[5] = "/\[b\](.+)\[\/b\]/Ui";
		$patterns[6] = "/\[i\](.+)\[\/i\]/Ui";
		$patterns[7] = "/\[u\](.+)\[\/u\]/Ui";
		$patterns[8] = '/\r\n\r\n/';
		$patterns[9] = '/\n\n/';
		$patterns[10] = '/\n/';
		$patterns[11] = '/\[q\](.+)\[\/q\]/Ui';
		#$patterns[12] = '/\[list\](\*(.+))+\[\/list\]/';
		$replacements[0] = '<a href="$1">$2</a>';
		$replacements[1] = '<img alt="$2" title="$2" src="$1" />';
		$replacements[2] = '<abbr title="$1">$2</abbr>';
		$replacements[3] = '<acronym title="$1">$2</acronym>';
		$replacements[4] = '<blockquote>$1</blockquote>';
		$replacements[5] = '<strong>$1</strong>';
		$replacements[6] = '<strong>$1</strong>';
		$replacements[7] = '<span style="text-decoration: underline;">$1</span>';
		$replacements[8] = '</p><p>';
		$replacements[9] = '</p><p>';
		$replacements[10] = '<br />';
		$replacements[11] = '<q>$1</q>';
		ksort($patterns);
		ksort($replacements);
		$ret = preg_replace($patterns, $replacements, $ret);
		$ret = "<p>".$ret."</p>";

		return $ret;
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
				$cleanline = preg_replace("/<!--META.*META-->\s\n?\r?/", "", $line);
				#if (preg_match("/\S/", $cleanline) == 0) $cleanline = "";
				
				$file_data .= $cleanline;
			}
		if (get_magic_quotes_gpc() ) $file_data = stripslashes($file_data);
		$this->abstract = $this->getAbstract();
		$this->data = $file_data;
		return $file_data;
	}

	# Read just the metadata for the entry into the class properties.
	# Hopefully, this will save us a little time reading large files.

	function readFileMetadata() {
		$fh = fopen($this->file, "r");
		$has_data = true;
		if (! $fh) return false;
		else 
			while (! feof($fh) && $has_data) {
				$line = fgets($fh);
				preg_match('/<!--META (.*): (.*) META-->/', $line, $matches);
				if ($matches) $this->addMetadata($matches[1], $matches[2]);
				else $has_data = false;
			}
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
		$fs = CreateFS();
		$header = $this->putMetadataFields();
		$file_data = $header.$this->data;
		if (! is_dir(dirname($this->file)) ) 
			$fs->mkdir_rec(dirname($this->file)); 
		$ret = $fs->write_file($this->file, $file_data);
		$fs->destruct();
		return $ret;
	}

}
?>
