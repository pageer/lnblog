<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005, 2006 Peter A. Geer <pageer@skepticats.com>

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
# Class: Pingback
# Represents a Pingback ping.  Pingbacks are similar in purpose to Trackbacks, 
# but are fully automated (as per the spec, no user interaction required) and
# implemented using XML-RPC.
# The Pingback specification is available at 
# <http://hixie.ch/specs/pingback/pingback>
#
# Events: 
# OnInit          - Fired when the object is about to initialize.
# InitComplete    - Fired after the object has been initialized.
# OnInsert        - Fired before a pingback is stored.
# InsertComplete  - Fired after a pingback is saved.
# OnDelete        - Fired when a pingback is about to be deleted.
# DeleteComplete  - Fired right after a pingback has been deleted.
# OnOutput        - Fired when starting to process for display.
# OutputComplete  - Fired when output is sent to the client.

require_once("lib/tb.php");

class Pingback extends Trackback {
	
	function Pingback($path=false) {
		$this->raiseEvent("OnInit");
		
		$this->target = '';
		$this->source = '';
		$this->title = '';
		$this->excerpt = '';
		$this->ip = '';
		$this->ping_date = '';
		$this->timestamp = '';
		$this->file = $path;
		if ($this->file) {
			if (! is_file($this->file)) 
				$this->file = $this->getFilename($this->file);
			if (is_file($this->file)) 
				$this->readFileData($this->file);
		}
		
		$this->raiseEvent("InitComplete");
	}
	
	# Method: uri
	# Get the URI for various functions

	function uri($type) {
		switch ($type) {
			case "permalink":
			case "pingback":
				return localpath_to_uri(dirname($this->file)).
				       "#".$this->getAnchor();
			case "delete":
				$qs_arr = array();
				$parent = $this->getParent();
				$entry_type = is_a($this, 'Article') ? 'article' : 'entry';
				$qs_arr['blog'] = $parent->parentID();
				$qs_arr[$entry_type] = $parent->entryID();
				$qs_arr['delete'] = $this->getAnchor();
				return make_uri(INSTALL_ROOT_URL."pages/delcomment.php", $qs_arr);
				#return localpath_to_uri(dirname($this->file)).
				#       "?delete=".$this->getAnchor();
		}
	}
	
	# Method: insert
	# Stores the pingback data in a file.  It is assumed that the source, 
	# target, title, and excerpt properties are set externally.
	#
	# Parameters: 
	# ent - The entry into which to insert this pingback.
	
	function insert($ent) {
		$this->raiseEvent("OnInsert");
		$ts = time();
		$this->ping_date = date("Y-m-d H:i:s T", $ts);
		$this->timestamp = time();
		$this->ip = get_ip();
		$this->file = mkpath($ent->localpath(), ENTRY_PINGBACK_DIR, 
		                     $ts.PINGBACK_PATH_SUFFIX);
		$dir = mkpath($ent->localpath(), ENTRY_PINGBACK_DIR);

		if (! $this->source) return false;

		if (! is_dir($dir)) {
			$ret = create_directory_wrappers($dir, ENTRY_PINGBACKS, get_class($ent));
		}
		$ret = $this->writeFileData($this->file);
		$this->raiseEvent("InsertComplete");
		return $ret;
	}
	
	# Method: isPingback
	# Determines if an object or file is a saved pingback.
	#
	# Parameters:
	# path - The *optional* path to the pingback data file.  If not given, 
	#        then the object's file property is used.
	#
	# Returns:
	# True if the data file exists and is under an entry pingback directory, 
	# false otherwise
	function isPingback($path=false) {
		if (!$path) $path = $this->file;
		if ( file_exists($path) && 
		     basename(dirname($path)) == ENTRY_PINGBACK_DIR ) {
			return true;
		} else {
			return false;
		}
	}
	
	# Method: getAnchor
	# Gets an anchor to the entry on the page.
	#
	# Returns:
	# The anchor to use for this pingback.
	
	function getAnchor() {
		$ret = basename($this->file);
		$ret = preg_replace("/.\w\w\w$/", "", $ret);
		$ret = "pingback".$ret;
		return $ret;
	}

	# Method: getFilename
	# Converts an anchor from <getAnchor> into a filename.
	#
	# Parameters:
	# anchor - The anchor to turn into a filename.
	#
	# Returns:
	# The name of the pingback file.
	
	function getFilename($anchor) {
		$ent = NewEntry();
		$ret = substr($anchor, 8);
		$ret .= PINGBACK_PATH_SUFFIX;
		$ret = mkpath($ent->localpath(),ENTRY_PINGBACK_DIR,$ret);
		$ret = realpath($ret);
		return $ret;
	}

	# Method: readFileData
	# Reads pingback data from a file.
	#
	# Parameters:
	# path - Optional path for the data file.  *Default* is the current file.

	function readFileData($path=false) {
		if (! $path) $path = $this->file;
		$file_data = file($path);
		foreach ($file_data as $line) {
			$line = trim($line);
			$pos = strpos($line, ":");
			$var = '';
			$dat = '';
			# Get the variable name and value, which starts 2 places after
			# the colon is found.
			if ($pos !== false) {
				$var = substr($line, 0, $pos);
				$dat = substr($line, $pos + 2);
			}
			# Set the properties.
			switch ($var) {
				case 'Source':
					$this->source = $dat;
					break;
				case 'Target':
					$this->target = $dat;
					break;
				case 'Title':
					$this->title = $dat;
					break;
				case 'Timestamp':
					$this->timestamp = $dat;
					break;
				case 'IP':
					$this->ip = $dat;
					break;
				case 'Date':
					$this->ping_date = $dat;
					break;
				default:
					$this->excerpt .= $line;
			}
		}
	}
	
	# Method: writeFileData
	# Write pingback data to a file.
	#
	# Parameters:
	# path - The path to which to write the data
	#
	# Returns:
	# True on success, false on failure.

	function writeFileData($path) {
		
		$fs = NewFS();
		if (! is_dir( dirname($path) ) ) {
			$fs->mkdir_rec(dirname($path));
		}
		$data = "Target: ".$this->target."\n".
		        "Source: ".$this->source."\n".
		        "Date: ".$this->ping_date."\n".
		        "Timestamp: ".$this->timestamp."\n".
		        "IP: ".$this->ip."\n".
		        "Title: ".$this->title."\n".
		        $this->excerpt;
		$ret = $fs->write_file($path, $data);
		$this->file = $path;
		$fs->destruct();
		return $ret;
	}
	
		# Method: get
	# Put the saved data into a template for display.
	#
	# Returns:
	# The data to be sent to the client.

	function get() {
		global $SYSTEM;
		$blog = NewBlog();
		$u = NewUser();
		$tpl = NewTemplate('pingback_tpl.php');
		$anchor = $this->getAnchor();
		$del_link = $this->uri("delete");

		$this->control_bar = array();
		$this->control_bar[] = 
			'<a href="'.$del_link.'" '.
			'onclick="return comm_del(this, \''.spf_("Delete %s?", $anchor).'\');">'
			._("Delete").'</a>';
		
		$this->raiseEvent("OnOutput");
		$tpl->set("SHOW_EDIT_CONTROLS", $SYSTEM->canModify($this->getParent(), $u) && $u->checkLogin() );
		$tpl->set("PB_SOURCE", $this->source);
		$tpl->set("PB_TARGET", $this->target);
		$tpl->set("CONTROL_BAR", $this->control_bar);
		$tpl->set("PB_PERMALINK", $this->permalink());
		$tpl->set("PB_ANCHOR", $this->getAnchor());
		$tpl->set("PB_DATE", $this->ping_date);
		$tpl->set("PB_TIMESTAMP", $this->timestamp);
		$tpl->set("PB_IP", $this->ip);
		$tpl->set("PB_TITLE", $this->title);
		$tpl->set("PB_EXCERPT", $this->excerpt);
		
		$this->raiseEvent("OutputComplete");
		return $tpl->process();
	}
	
	# The following Trackback methods are not to be inherited by subclasses.  
	# However, PHP4 does not have a way to express this.  Therefore I'll just 
	# put them here and give them an empty implementation.
	function incomingPing() { return false; }
	function send() { return false; }
	function receive() { return false; }
	function getPostData() { return false; }
	
	# Method: fetchPage
	# Requests a URL from a remote host and returns the resulting data.
	#
	# Parameters:
	# url     - The URL to fetch.
	# headers - *Optional* boolean to only fetch the page headers.
	#           *Defaults* to false.
	#
	# Returns:
	# A string containing the HTTP headers and body of the requested URL.
	
	function fetchPage($url, $headers=false) {
		if (extension_loaded('curl')) {
			
			$hnd = curl_init();
			curl_setopt($hnd, CURLOPT_URL, $url);
			curl_setopt($hnd, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($hnd, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($hnd, CURLOPT_HEADER, 1);
			if ($headers) curl_setopt($hnd, CURLOPT_NOBODY, 1);
			$response = curl_exec($hnd);
			
		} else {
			
			$url_bits = parse_url($url);
			$host = $url_bits['host'];
			$path = isset($url_bits['path']) ? $url_bits['path'] : "/";
			$port = isset($url_bits['port']) ? $url_bits['port'] : 80;
			$query = isset($url_bits['query']) ? $url_bits['query'] : '';
			
			# Open a socket.
			$fp = @fsockopen($host, $port);
			if (!$fp) return false;
	
			# Create the HTTP request to be sent to the remote host.
			if ($query) $path .= '?'.$query;
			if ($headers) $method = 'HEAD';
			else $method = 'GET';
			$data = $method." ".$path."\r\n".
					"Host: ".$host."\r\n".
					"Connection: close\r\n\r\n";
			
			# Send the data and then get back any response.
			fwrite($fp, $data);
			$response = '';

			while (! feof($fp)) {
				$s = fgets($fp);
				$response .= $s;
			}
			fclose($fp);
		}
		return $response;
	}
	
	# Method: checkPingbackEnabled
	# Checks if a given URL is pingback-enabled or not.
	#
	# url - The URL to check.
	#
	# Returns:
	# If the resource has an X-Pingback header or a link element with the
	# rel="pingback" attribute set, then returns the URL specified in the header
	# (if it exists) or the link element.  Returns false otherwise.
	
	function checkPingbackEnabled($url) {
		# First check the page headers.
		$pageheaders = $this->fetchPage($url, true);
		$pingback_server = '';
		$text_data = false;
		
		# Look for an X-Pingback header in the page data.
		$lines = explode("\n", $pageheaders);
		foreach ($lines as $l) {
			$s = trim($l);
			$matches = array();
			
			$ret = preg_match('/^X-Pingback: ?(.+)$/', $s, $matches);
			if ($ret && ! $pingback_server) {
				$pingback_server = $matches[1];
			}
			
			# Check for the content type.  We probably don't want to fetch the 
			# body of the link if it's not a text type, since we could, 
			# theoretically, end up linking to a 5MB MP3 file.
			# Note that I'm not sure this is a good idea.
			$ret = preg_match('|^Content-Type: ?text/(.+)$|', $s, $matches);
			if ($ret) $text_data = true;
			
			# The page headers should end with a blank line.
			# So, at the first blank line we hit, abandon searching 
			# for the header.
			if ($s == '') break;
		}
		
		if (! $pingback_server && $text_data) {
			$pagedata = $this->fetchPage($url);
			$ret = preg_match('|<link rel="pingback" href="([^"]+)" ?/?>|',
				              $pagedata, $matches);
			if ($ret) {
				$pingback_server = $matches[1];
				$search = array('&amp;', '&lt;', '&gt;', '&quot;');
				$replace = array('&', '<', '>', '"');
				$pingback_server = str_replace($search, $replace, $pingback_server);
			} else {
				$pingback_server = false;
			}
		}
		return $pingback_server;
	}

}
?>
