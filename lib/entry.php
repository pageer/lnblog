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

require_once("lib/utils.php");
require_once("lib/lnblogobject.php");

/*
Class: Entry
An abstract class representing entries of all types in the blog database.

Inherits:
<LnBlogObject>
*/

class Entry extends LnBlogObject{

	# Property: id
	# An ID for the object that is unique across the class (not used).
	var $id;
	# Property: uid
	# The user ID of this object's owner.
	var $uid;
	# Property: ip
	# The IP address logged for this object at last modification.
	var $ip;
	# Property: date
	# The human-readable date when the object was last modified.
	var $date;
	# Property: timestamp
	# The UNIX timestamp when the object was last modified.
	var $timestamp;
	# Property: post_date
	# Human-readable date when the object was created.
	var $post_date;
	# Property: post_ts
	# UNIX timestamp when the object was created.
	var $post_ts;
	# Property: subject
	# The subject text associated with this object.
	var $subject;
	# Property: abstract
	# An abstract of the text of this object (not used).
	var $abstract;
	# Property: tags
	# A comma-delimited list of tags applied to this entry.
	var $tags = "";
	# Property: data
	# The main text data of this object.  May be one of several different 
	# kinds of markup.
	var $data;
	# Property: file
	# The path to the file that holds data for this entry.  Note that this is
	# specific to filesystem storage and is for internal use only.
	var $file;
	# Property: has_html
	# Holds the type of markup used in the data property.  This can be one
	# of several defined constants, includine <MARKUP_NONE>, <MARKUP_BBCODE>, 
	# and <MARKUP_HTML>.
	var $has_html = MARKUP_NONE;

	/*
	Method: data
	Set or return the data property.  If the optional value parameter is set
	to a true value (i.e. a non-empty string), then this value is set to the
	data property.  Otherwise, the data property is returned.
	*/
	function data($value=false) {
		if ($value) $this->data = $value;
		else return $this->data;
	}

	/*
	Method: tags
	Set or return an array of tags for this entry.  Each tag is an arbitrary 
	string entered by the user with no inherent meaning.
	*/
	function tags($list=false) {
		if ($list) {
			$this->tags = implode(TAG_SEPARATOR, $list);
		} else {
			if (! $this->tags) return false;
			$ret = explode(TAG_SEPARATOR, $this->tags);
			foreach ($ret as $key=>$val) {
				$ret[$key] = trim($val);
			}
			return $ret;
		}
	}

	/*
	Method: permalink
	Abstract function that returns the object's permalink.  
	Child classes *must* over-ride this.
	*/
	function permalink() { return ""; }

	/*
	Method: queryStringToID
	Abstract function that converts a query string into a unique identifier 
	for an object.  
	Child classes *must* over-ride this function.
	*/
	function queryStringToID() { return false; }

	/*
	Method: absolutizeBBCodeURI
	A function to search LBCode marked-up text and convert the URIs in
	links and images from relative to absolute.

	Parameters:
	data        - The string containing the LBCode markup to absolutize.
	current_uri - The URI to which the relative links are relative.

	Returns:
	A string with the markup in the data parameter, but with relative img and
	url tags converted to absolute URIs under the current_uri parameter.
	*/
	function absolutizeBBCodeURI($data, $current_uri) {
		$ret = preg_replace("/\[img(-?\w+)?=([^\/]+)\]/",
		                    "[img$1=".$current_uri."$2]", $data);
		#echo "<p>$ret</p>";
		$ret = preg_replace("/\[url=([^\/:@]+)\]/", 
		                    "[url=".$current_uri."$1]", $ret);
		return $ret;
	}

	/*
	Method: stripHTML
	Strip HTML code out of a string.  Note that the <UNICODE_ESCAPE_HACK>
	configuration constant can be used to switch between using the PHP
	htmlentities() and htmlspecialchars() functions to sanitize input.
	This is because htmlentities() has a nasty habit of mangling Unicode.

	Parameters:
	data - The string to strip.

	Returns:
	A copy of data with HTML special characters such as angle brackets and
	ampersands converted into their corresponding HTML entities.  This will
	cause them to display in a web page as characters, not HTML markup.
	*/
	function stripHTML($data) {
		
		if (UNICODE_ESCAPE_HACK) {
			$ret = htmlentities($data);
			$ret = preg_replace("/&amp;(#\d+);/Usi", "&$1;", $ret);
			$ret = preg_replace("/&amp;(\w{2,7});/Usi", "&$1;", $ret);
		} else {
			$ret = htmlspecialchars($data);
		}
		return $ret;
	}

	# Removes all markup (both HTML and LBCode tags) from the entry data 
	# and returns just plain text.

	function getPlainText() {
		if ($this->has_html == MARKUP_HTML) {
			$ret = strip_tags($this->data);
		} elseif ($this->has_html == MARKUP_BBCODE) {
			$patterns = array('/\[.+\]/Usi', '/\[\/.+\]/Usi');
			$replacements = array('', '', '', '');
			$ret = preg_replace($patters, $replacements, $this->data);
		} else $ret = $this->data;
		return $ret;
	}

	function addHTML($data, $use_nofollow=false) {
		$ret = $data;
		$patterns[0] = "/((http|https|ftp):\/\/\S*)/i";
		$patterns[1] = '/\r\n\r\n/';
		$patterns[2] = '/\n\n/';
		$patterns[3] = '/\n/';
		if ($use_nofollow) {
			$replacements[0] = '<a href="$1" rel="nofollow">$1</a>';
		} else {
			$replacements[0] = '<a href="$1">$1</a>';
		}
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
		
		$patterns[0] = "/\[url=(\S+)\](.+)\[\/url\]/Usi";
		$patterns[1] = "/\[img=(.+)](.+)\[\/img\]/Usi";
		$patterns[2] = "/\[ab=(.+)\](.+)\[\/ab\]/Usi";
		$patterns[3] = "/\[ac=(.+)\](.+)\[\/ac\]/Usi";
		$patterns[4] = "/\[quote\](.+)\[\/quote\]/Usi";
		$patterns[5] = "/\[quote=(.+)\](.+)\[\/quote\]/Usi";
		$patterns[6] = "/\[b\](.+)\[\/b\]/Usi";
		$patterns[7] = "/\[i\](.+)\[\/i\]/Usi";
		$patterns[8] = "/\[u\](.+)\[\/u\]/Usi";
		$patterns[9] = '/\[q\](.+)\[\/q\]/Usi';
		$patterns[10] = '/\[q=(.+)\](.+)\[\/q\]/Usi';
		$patterns[11] = "/(\r?\n\s*)?\[list\]\s*\r?\n(.+)\[\/list\](\s*\r?\n)?/Usi";
		$patterns[12] = "/(\r?\n\s*)?\[numlist\]\s*\r?\n(.+)\[\/numlist\](\s*\r?\n)?/Usi";
		$patterns[13] = "/\[\*\](.*)\r?\n/Usi";
		$patterns[14] = "/\[code\](.*)\[\/code\]/Usi";
		$patterns[15] = "/\[t\](.*)\[\/t\]/Usi";
		$patterns[16] = "/\[img-left=(.+)\](.+)\[\/img-left\]/Usi";
		$patterns[17] = "/\[img-right=(.+)\](.+)\[\/img-right\]/Usi";
		$patterns[18] = "/\[h\](.*)\[\/h\]/Usi";
		
		$replacements[0] = '<a href="$1">$2</a>';
		$replacements[1] = '<img alt="$2" title="$2" src="$1" />';
		$replacements[2] = '<abbr title="$1">$2</abbr>';
		$replacements[3] = '<acronym title="$1">$2</acronym>';
		$replacements[4] = '</p><blockquote><p>$1</p></blockquote><p>';
		$replacements[5] = '</p><blockquote cite="$1"><p>$2</p></blockquote><p>';
		$replacements[6] = '<strong>$1</strong>';
		$replacements[7] = '<em>$1</em>';
		$replacements[8] = '<span style="text-decoration: underline;">$1</span>';
		$replacements[9] = '<q>$1</q>';
		$replacements[10] = '<q cite="$1">$2</q>';
		$replacements[11] = "</p><ul>$2</ul><p>";
		$replacements[12] = "</p><ol>$2</ol><p>";
		$replacements[13] = '<li>$1</li>';
		$replacements[14] = '<code>$1</code>';
		$replacements[15] = '<tt>$1</tt>';
		$replacements[16] = '<img alt="$2" title="$2" style="float: left; clear: none;" src="$1" />';
		$replacements[17] = '<img alt="$2" title="$2" style="float: right; clear: none;" src="$1" />';
		$replacements[18] = '</p><h'.LBCODE_HEADER_WEIGHT.'>$1</h'.LBCODE_HEADER_WEIGHT.'><p>';
		
		
		$whitespace_patterns[0] = '/\r\n\r\n/';
		$whitespace_patterns[1] = '/\n\n/';
		$whitespace_patterns[2] = '/\n/';
		$whitespace_replacements[0] = '</p><p>';
		$whitespace_replacements[1] = '</p><p>';
		$whitespace_replacements[2] = '<br />';
		ksort($patterns);
		ksort($replacements);
		$ret = preg_replace($patterns, $replacements, $ret);
		$ret = preg_replace($whitespace_patterns, $whitespace_replacements, $ret);
		$ret = "<p>".$ret."</p>";
	
		# Strip out extraneous empty paragraphs.
		return preg_replace('/<p><\/p>/', '', $ret);
	}

	# Method: markup
	# Apply appropriate markup to the entry data.
	#
	# Parameters:
	# data         - *Optional* data to markup.  If not specified, use the 
	#                data property of the current object.
	# use_nofollow - Apply rel="nofollow" to links.  *Default* is false.
	#
	# Returns:
	# A string with markup applied.
	
	function markup($data="", $use_nofollow=false) {
		if (! $data) $data = $this->data;
		switch ($this->has_html) {
			case MARKUP_NONE:
				$ret = $this->stripHTML($data);
				$ret = $this->addHTML($ret, $use_nofollow);
				break;
			case MARKUP_BBCODE:
				$ret = $this->absolutizeBBCodeURI($data, $this->permalink() );
				$ret = $this->stripHTML($ret);
				$ret = $this->bbcodeToHtml($ret);
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

	# Method: prettyDate
	# Get a human-readable date from a timestamp.
	#
	# Parameters:
	# ts - *Optional* timestamp for the date.  If unset, use time().
	#
	# Returns:
	# A string with a formatted, localized date.

	function prettyDate($ts=false) {
		$date_ts = $ts ? $ts : $this->timestamp;
		# If we don't aren't passed a timestamp and don't already have one,
		# then just use the current time.
		if (! $date_ts) $date_ts = time();
		$print_date = fmtdate(ENTRY_DATE_FORMAT, $date_ts);
		return $print_date;
	}

	# Add metadata from the file to the object.  Unknown metadata will be 
	# ignored, while recognized data will be set to class properties.
	
	function addMetadata($key, $val) {
		switch ($key) {
			case "PostID":        $this->id = $val; break;
			case "UserID":        $this->uid = $val; break;
			case "Date":          $this->date = $val; break;
			case "PostDate":      $this->post_date = $val; break;
			case "Timestamp":     $this->timestamp = $val; break;
			case "PostTimestamp": $this->post_ts = $val; break;
			case "IP":            $this->ip = $val; break;
			case "Subject":       $this->subject = $val; break;
			case "HasHTML":       $this->has_html = $val; break;
			case "Tags":          $this->tags = $val; break;
		}
	}
	
	function readFileData() {
		#$data = file($this->store_path . "/" . $this->datafile);
		$data = file($this->file);
		$file_data = "";
		if (! $data) $file_data = false;
		else 
			foreach ($data as $line) {
				preg_match('/<!--META ([\w|\s|-]*): (.*) META-->/', $line, $matches);
				if ($matches) $this->addMetadata($matches[1], $matches[2]);
				$cleanline = preg_replace("/<!--META.*META-->\s\r?\n?\r?/", "", $line);
				#if (preg_match("/\S/", $cleanline) == 0) $cleanline = "";
				
				$file_data .= $cleanline;
			}
		#if (get_magic_quotes_gpc() ) $file_data = stripslashes($file_data);
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
		$ret["PostDate"] = $this->post_date;
		$ret["Timestamp"] =  $this->timestamp;
		$ret["PostTimestamp"] = $this->post_ts;
		$ret["IP"] = $this->ip;
		$ret["Subject"] = $this->subject;
		$ret["HasHTML"] = $this->has_html ? 1 : 0;
		$ret["Tags"] = $this->tags;
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
		$fs = NewFS();
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
