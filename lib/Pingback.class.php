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

class Pingback extends Trackback {
	
	public $target = '';
	public $source = '';
	public $title = '';
	public $excerpt = '';
	public $ip = '';
	public $ping_date = '';
	public $timestamp = '';
	public $file = '';
    public $exclude_fields = array('fs');

	public function __construct($path=false, $fs = null, $http_client = null) {
        $this->fs = $fs ?: NewFS();
        $this->http_client = $http_client ?: new HttpClient();

		$this->raiseEvent("OnInit");

		$this->file = $path;
		if ($this->file) {
			if (! $this->fs->is_file($this->file)) 
				$this->file = $this->getFilename($this->file);
			if ($this->fs->is_file($this->file)) 
				$this->readFileData($this->file);
		}
		
		$this->raiseEvent("InitComplete");
	}
	
	# Method: globalID
	# Get the global identifier for this trackback.
	function globalID() {
		$parent = $this->getParent();
		$id = $parent->globalID();
		if (defined('ENTRY_PINGBACK_DIR') && ENTRY_PINGBACK_DIR) {
			$id .= '/'.ENTRY_PINGBACK_DIR;
		}
		$id .= '/#'.$this->getAnchor();
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

		if (! $this->fs->is_dir($dir)) {
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
		if ( $this->fs->file_exists($path) && 
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
		$ret = $this->fs->realpath($ret);
		return $ret;
	}
	
	function readOldFile($path) {
		$file_data = $this->fs->file($path);
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
	
	# Method: get
	# Put the saved data into a template for display.
	#
	# Returns:
	# The data to be sent to the client.

	function get() {
		$blog = NewBlog();
		$u = NewUser();
		$tpl = NewTemplate('pingback_tpl.php');
		$anchor = $this->getAnchor();
		$del_link = $this->uri("delete");

		$this->control_bar = array();
		$this->control_bar[] = 
			'<a href="'.$del_link.'" class="deletelink">'._("Delete").'</a>';
			
		$this->raiseEvent("OnOutput");
		$tpl->set("SHOW_EDIT_CONTROLS", System::instance()->canModify($this->getParent(), $u) && $u->checkLogin() );
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
		$tpl->set("PB_LOCAL", $this->isLocal());
		
		$this->raiseEvent("OutputComplete");
		return $tpl->process();
	}
	
	# Method: isLocal
	# Determines whether or not the pingback is to an entry on the 
	# current blog and/or server.
	#
	# Returns:
	# True if the source and target are on the same host, false otherwise.
	
	function isLocal() {
		$source_info = parse_url($this->source);
		$target_info = parse_url($this->target);
		return $source_info['host'] == $target_info['host'];
	}
	
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
        $client = new HttpClient();
        return $client->fetchUrl($url, $headers);
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
		$pageheaders = $this->http_client->fetchUrl($url, true);
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
			$pagedata = $this->http_client->fetchUrl($url);
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
