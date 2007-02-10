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
require_once("lib/xml.php");
#require_once("lib/lbparse.php");
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
	# Property: custom_fields
	# An array of custom fields for the entry, with keys being the field name
	# for use in the data structure and configuration files and the value
	# being a short description to display to the user.
	var $custom_fields = array();
	# Property: metadata_fields
	# An array of property->var pairs.  These are the object member 
	# variable names and the data file variable names respectively.
	# They are used to retreive data from persistent storage.
	var $metadata_fields = array("id"=>"PostID", "uid"=>"UserID",
		"date"=>"Date", "post_date"=>"PostDate", 
		"timestamp"=>"Timestamp", "post_ts"=>"PostTimeStemp", 
		"ip"=>"IP", "subject"=>"Subject", "has_html"=>"HasHTML",
		"tags"=>"Tags", "custom"=>"Custom");

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
	Method: getParent
	Abstract function to return the parent object of the current object.
	This will be a Blog object for BlogEntry or Article objects, and a 
	BlogEntry or Article for BlogComment or TrackBack objects.
	Child classes *must* over-ride this method.
	*/
	function getParent() { return false; }
	
	/*
	Method: baselink
	Gets a link to the object's base directory, to use for converting relative
	to absolute paths.  I some cases, this is just the permalink.

	Returns:
	An absolute URI.
	*/
	function baselink() { return $this->uri(); }
	
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
	url tags converted to absolute URIs.  If the relative URI contains no 
	slashes, colons, or ampersands, the relative URI given will be interpreted
	as under the under the current_uri parameter.  If it contains slashes, but
	no colons or ampersands, it will interpreted as relative to the
	blog root, if there is a current blog, and if the given URI starts with a 
	slash, it will be interpreted as relative to the DOCUMENT_ROOT, if it is set.
	*/
	function absolutizeBBCodeURI($data, $current_uri) {
		$blog = $this->getParent();
		$ret = preg_replace("/\[img(-?\w+)?=([^\/\:]+)\]/",
		                    "[img$1=".$current_uri."$2]", $data);
		$ret = preg_replace("/\[url=([^\/\:@]+)\]/", 
		                    "[url=".$current_uri."$1]", $ret);
		if (defined("DOCUMENT_ROOT")) {
			$ret = preg_replace("/\[img(-?\w+)?=\/([^\:]+)\]/",
			          "[img$1=".localpath_to_uri(DOCUMENT_ROOT)."$2]", $ret);
			$ret = preg_replace("/\[url=\/([^\:@]+)\]/", 
			          "[url=".localpath_to_uri(DOCUMENT_ROOT)."$1]", $ret);
		}
		if (is_a($blog, 'Blog')) {
			$ret = preg_replace("/\[img(-?\w+)?=([^\:]+)\]/",
			                    "[img$1=".$blog->uri('base')."$2]", $ret);
			$ret = preg_replace("/\[url=([^\:@]+)\]/", 
			                    "[url=".$blog->uri('base')."$1]", $ret);
		}
		return $ret;
	}

	/*
	Method: absolutizeHTMLURI
	A version of <absolutizeBBCodeURI> that works with HTML markup.

	Parameters:
	data        - The string containing the HTML markup to absolutize.
	current_uri - The URI to which the relative links are relative.

	Returns:
	The markup in the data parameter with href and src attributes absolutized
	according to the same rules as apply with <absolutizeBBCodeURI>.
	*/
	function absolutizeHTMLURI($data, $current_uri) {
		$blog = $this->getParent();
		$ret = preg_replace("/src=['\"]([^\/\:]+)['\"]/",
		                    "src=\"".$current_uri."$1\"", $data);
		$ret = preg_replace("/href=['\"]([^\/\:@]+)['\"]/",
		                    "href=\"".$current_uri."$1\"", $ret);
		if (defined("DOCUMENT_ROOT")) {
			$ret = preg_replace("/src=['\"]\/([^\:]+)['\"]/", 
		                    "src=\"".localpath_to_uri(DOCUMENT_ROOT)."$1\"", $ret);
			$ret = preg_replace("/href=['\"]\/([^\:@]+)['\"]/", 
		                    "href=\"".localpath_to_uri(DOCUMENT_ROOT)."$1\"", $ret);
		}
		if (is_a($blog, 'Blog')) {
			$ret = preg_replace("/src=['\"]([^\:]+)['\"]/",
			                    "src=\"".$blog->uri('base')."$1\"", $ret);
			$ret = preg_replace("/href=['\"]([^\:@]+)['\"]/", 
			                    "href=\"".$blog->uri('base')."$1\"", $ret);
		}
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
			$ret = preg_replace($patterns, $replacements, $this->data);
		} else $ret = $this->data;
		return $ret;
	}

	function addHTML($data, $use_nofollow=false) {
		$ret = $data;
		$patterns[0] = "/((http|https|ftp):\/\/\S*)/i";
		$patterns[1] = '/\r\n(\r\n)+/';
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

	# Method: bbcodeToHTML
	# Converts LBCode markup into HTML.
	#
	# Parameters:
	# data  - The data string to convert.
	# strip - *Optional* boolean indicating if the LBCode should just be
	#         stripped rather than converted.  Defaults to false.
	#
	# Returns:
	# A string with the converted text.
	
	#function bbcodeToHTML2($data, $strip=false) {
	#	$p = new LBParser($data);
	#	return '<p>'.$p->parse().'</p>';
	#}
	
	function bbcodeToHTML($data, $strip=false) {
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
		$patterns[11] = "/(\r?\n\s*)?\[list\]\s*\r?\n(.+)\[\/list\](\s*\r?\n)?/si";
		$patterns[12] = "/(\r?\n\s*)?\[numlist\]\s*\r?\n(.+)\[\/numlist\](\s*\r?\n)?/si";
		$patterns[13] = "/\[\*\](.*)\r?\n/Usi";
		$patterns[14] = "/\[code\](.*)\[\/code\]/Usi";
		$patterns[15] = "/\[t\](.*)\[\/t\]/Usi";
		$patterns[16] = "/\[img-left=(.+)\](.+)\[\/img-left\]/Usi";
		$patterns[17] = "/\[img-right=(.+)\](.+)\[\/img-right\]/Usi";
		$patterns[18] = "/\[h\](.*)\[\/h\]/Usi";
		$patterns[19] = "/\[color=(.+)\](.+)\[\/color\]/Usi";

		
		$whitespace_patterns[0] = '/\r\n\r\n/';
		$whitespace_patterns[1] = '/\n\n/';
		$whitespace_patterns[2] = '/\r\n/';
		$whitespace_patterns[3] = '/\n/';
		$whitespace_patterns[4] = '/\t/';
		
		/*
		$code_patterns[0] = '/(<code.*)<\/p><p>(.*<\/code>)/U';
		$code_patterns[1] = '/(<code.*)<br \/>(.*<\/code>)/U';
		$code_replacements[0] = '$1'."\n\n".'$2';
		$code_replacements[1] = '$1'."\n".'$2';
		*/
		
		if ($strip) {
			$replacements[0] = '$2';
			$replacements[1] = '';
			$replacements[2] = '$2';
			$replacements[3] = '$2';
			$replacements[4] = '$1';
			$replacements[5] = '$2';
			$replacements[6] = '$1';
			$replacements[7] = '$1';
			$replacements[8] = '$1';
			$replacements[9] = '$1';
			$replacements[10] = '$2';
			$replacements[11] = '$2';
			$replacements[12] = '$2';
			$replacements[13] = '$1';
			$replacements[14] = '$1';
			$replacements[15] = '$1';
			$replacements[16] = '';
			$replacements[17] = '';
			$replacements[18] = '$1';
			$replacements[19] = '$2';
			
			$whitespace_replacements[0] = "\r\n\r\n";
			$whitespace_replacements[1] = "\n\n";
			$whitespace_replacements[2] = "\r\n";
			$whitespace_replacements[3] = "\n";
			$whitespace_replacements[4] = "\t";
			
			
		} else {
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
			$replacements[11] = '</p><ul>$2</ul><p>';
			$replacements[12] = '</p><ol>$2</ol><p>';
			$replacements[13] = '<li>$1</li>';
			$replacements[14] = '<code>$1</code>';
			$replacements[15] = '<tt>$1</tt>';
			$replacements[16] = '<img alt="$2" title="$2" style="float: left; clear: none;" src="$1" />';
			$replacements[17] = '<img alt="$2" title="$2" style="float: right; clear: none;" src="$1" />';
			$replacements[18] = '</p><h'.LBCODE_HEADER_WEIGHT.'>$1</h'.LBCODE_HEADER_WEIGHT.'><p>';
			$replacements[19] = '<span style="color: $1">$2</span>';
			
			$whitespace_replacements[0] = '</p><p>';
			$whitespace_replacements[1] = '</p><p>';
			$whitespace_replacements[2] = '<br />';
			$whitespace_replacements[3] = '<br />';
			$whitespace_replacements[4] = '&nbsp;&nbsp;&nbsp;';
			
		}
		
		ksort($patterns);
		ksort($replacements);
		$ret = preg_replace($patterns, $replacements, $ret);
		$ret = preg_replace($whitespace_patterns, $whitespace_replacements, $ret);
		
		if (! $strip) {
			$ret = "<p>".$ret."</p>";
			# Strip out extraneous empty paragraphs.
			$ret = preg_replace('/<p><\/p>/', '', $ret);
		}
	
		return $ret;
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
				$ret = $this->absolutizeBBCodeURI($data, $this->baselink() );
				$ret = $this->stripHTML($ret);
				$ret = $this->bbcodeToHtml($ret);
				break;
			case MARKUP_HTML:
				$ret = $this->absolutizeHTMLURI($data, $this->baselink());
				break;
		}
		return $ret;
	}

	# Method: getAbstract
	# A quick function to get a plain text abstract of the entry data
	# by grabbing the first paragraph of text or the first N characters,
	# whichever comes first.  Markup is removed in the process.
	# Note that it attempts to do word wrapping to avoid cutting words off in 
	# the middle.
	#
	# Parameters:
	# nuchars - *Optional* number of characters.  *Defaults* to 500.
	#
	# A string containing the abstract text, with all markup stripped out.

	function getAbstract($numchars=500) {
		if (!$this->data || $numchars < 1) return '';
		if ($this->has_html == MARKUP_BBCODE) {
			$data = $this->bbcodeToHTML($this->data, true);
		} elseif ($this->has_html == MARKUP_HTML) {
			$data = strip_tags($this->data);
		} else {
			$data = $this->stripHTML($this->data);
		}
		
		$data = explode("\n", $data);
		if (strlen($data[0]) > $numchars) {
			return wordwrap($data[0],$numchars);
		} else {
			return $data[0];
		}
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

	# Method: readFileData
	# Reads entry data from a file.  As of verions 0.8.2, data is stored in XML
	# format, with the elements corresponding directly to class properties.
	#
	# In previous versions, file metadata is enclosed in META tags
	# which are HTML comments, in one-per-line format.  Here is an example.
	# |<!--META Subject: This is a subject META-->
	# Variables are created for every such line which is referenced in the 
	# metadata_fields property of the object.  The metadata_fields property is
	# an associative array where the element key is the propery of the object to
	# which the value is assigned and the element valueis the case-insensitive 
	# variable used in the file, as with "Subject" in the example above.
	#
	# There is also a custom_fields property.  This is an associative array, 
	# just as metadata_fields.  If custom_fields is populated, its members are
	# merged into metadata_fields, in effect adding elements to the standard
	# metadata fields.
	#
	# Returns:
	# The body of the entry as a string.
	
	function readFileData() {

		if (substr($this->file, strlen($this->file)-4) != ".xml") {
			$this->readOldFile();
		} else {
			$this->deserializeXML($this->file);
		}

		if (! $this->abstract) $this->abstract = $this->getAbstract();
		if (is_subclass_of($this, 'BlogEntry')) {
			$this->id = str_replace(PATH_DELIM, '/', 
			                        substr(dirname($this->file), 
									        strlen(DOCUMENT_ROOT)));
		} else {
			$this->id = str_replace(PATH_DELIM, '/', 
			                        substr($this->file, strlen(DOCUMENT_ROOT)));
		}
		return $this->data;
	}

	function readOldFile() {
		$data = file($this->file);
		$file_data = "";
		if (! $data) $file_data = false;
		else 
			foreach ($this->custom_fields as $fld=>$desc) {
				$this->metadata_fields[$fld] = $fld;
			}
			$lookup = array_flip($this->metadata_fields);
			foreach ($data as $line) {
				preg_match('/<!--META ([\w|\s|-]*): (.*) META-->/', $line, $matches);
				if ($matches && isset($lookup[strtolower($matches[1])])) {
					$this->$lookup[strtolower($matches[1])] = $matches[2];
				}
				$cleanline = preg_replace("/<!--META.*META-->\s\r?\n?\r?/", "", $line);
			
				$file_data .= $cleanline;
			}
		$this->data = $file_data;
	}

	# Method: writeFileData
	# Write entry data to a file.  The contents of the file are determined by
	# the properties of the object and the contents of the metadata_fields and 
	# custom_fields properties, just as with <readFileData>.  This function
	# writes the metadata to HTML comments, as mentioned above, while the body
	# data is written at the end of the file.  Note that, in order for the file
	# to be written, the file property of the object must be set.
	#
	# Returns:
	# True if the file write is successful, false otherwise.
	
	function writeFileData() {
		$fs = NewFS();

		if (defined("USE_OLD_ENTRY_FORMAT")) {
			$file_data = $this->writeOldFile();
		} else {
			$file_data = $this->serializeXML();
		}

		if (! is_dir(dirname($this->file)) ) 
			$fs->mkdir_rec(dirname($this->file)); 
		$ret = $fs->write_file($this->file, $file_data);
		$fs->destruct();
		return $ret;
	}
	
	function writeOldFile() {
		$header = '';
		foreach ($this->custom_fields as $fld=>$desc) {
			$this->metadata_fields[$fld] = $fld;
		}
		foreach ($this->metadata_fields as $mem=>$fvar) {
			$header .= "<!--META ".$fvar.": ".
				(isset($this->$mem) ? $this->$mem : "")." META-->\n";
		}
		$file_data = $header.$this->data;
		return $file_data;
	}

}
?>
