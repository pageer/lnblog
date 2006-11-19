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

# Class: Trackback
# Class for TrackBack entries.  This attempts to comply with theTrackBack
# specification at http://www.sixapart.com/pronet/docs/trackback_spec
# This is used to recieve and send TrackBack pings as well as to access
# locally stored ping data.
#
# Inherits: LnBlogObject
#
# Events:
# OnInit          - Fired when the object is about to initialize.
# InitComplete    - Fired after the object has been initialized.
# POSTRetreived   - Fired when POST data for a trackback is retreived.
# OnInsert        - Fired before a trackback is stored.
# InsertComplete  - Fired after a trackback is saved.
# OnDelete        - Fired when a trackback is about to be deleted.
# DeleteComplete  - Fired right after a trackback has been deleted.
# OnReceive       - Fired when starting to receive a ping.
# ReceiveComplete - Fired after receiving a ping.
# OnSend          - Fired before sending a ping.
# SendComplete    - Fired after sending a ping.
# OnOutput        - Fired when starting to process for display.
# OutputComplete  - Fired when output is sent to the client.

require_once("lib/utils.php");
require_once("lib/creators.php");
require_once("lib/lnblogobject.php");

class Trackback extends LnBlogObject {

	# The only required element is the URL

	var $title;
	var $blog;
	var $data;
	var $url;
	var $ping_date;
	var $ip;
	var $file;

	function Trackback($path=false) {
		$this->raiseEvent("OnInit");
		
		$this->title = '';
		$this->blog = '';
		$this->data = '';
		$this->url = '';
		$this->ip = '';
		$this->ping_date = false;
		$this->file = $path;
		if ($this->file) {
			if (! is_file($this->file)) $this->file = $this->getFilename($this->file);
			if (is_file($this->file)) $this->readFileData($this->file);
		}
		
		$this->raiseEvent("InitComplete");
	}

	# Method: getParent
	# Gets a copy of the parent object.
	#
	# Returns:
	# A BlogEntry or Article object, depending on the context.
	
	function getParent() {
		return NewEntry();
	}

	# Method: isTrackback
	# Determines if an object or file is a saved trackback.
	#
	# Parameters:
	# path - The *optional* path to the trackback data file.  If not given, 
	#        then the object's file property is used.
	#
	# Returns:
	# True if the data file exists and is under an entry trackback directory, 
	# false otherwise

	function isTrackback($path=false) {
		if (!$path) $path = $this->file;
		if ( file_exists($path) && 
		     basename(dirname($path)) == ENTRY_TRACKBACK_DIR ) {
			return true;
		} else {
			return false;
		}
	}
	
	# Method: uri
	# Get the URI for various functions

	function uri($type) {
		switch ($type) {
			case "permalink":
			case "trackback":
				return localpath_to_uri(dirname($this->file)).
				       "#".$this->getAnchor();
			case "delete":
				return localpath_to_uri(dirname($this->file)).
				       "?delete=".$this->getAnchor();
		}
	}

	# Method: getPostData
	# Pulls the trackback data out of the POST and into the object.
	#
	# As per the TrackBack specification located at
	# <http://www.sixapart.com/pronet/docs/trackback_spec>, the interface for 
	# POSTs is as follows.
	# title     - The title of the pinging post.
	# excerpt   - An excerpt from the text of the pinging post.
	# blog_name - The name of the blog to which the pinging post belongs.
	# url       - The URL of the pinging post.  This is the only required field.

	function getPostData() {
		$this->title = htmlspecialchars(POST("title"));
		$this->data = htmlspecialchars(POST("excerpt"));
		$this->blog = htmlspecialchars(POST("blog_name"));
		$this->url = POST("url");
		$this->ip = get_ip();
		$this->ping_date = date('r');
		$this->raiseEvent("POSTRetreived");
	}
	
	# Method: send
	# Send a TrackBack ping without using a form.
	#
	# Parameters:
	# url - The URL to which the trackback ping will be sent.
	#
	# Returns: 
	# An associative array with 'error', 'message', and 'response' elements.
	# The error element contains the trackback return code from the remote
	# server.  The message element contains the error message if there was
	# one.  Note that a return code of 0 indicates success, while other values
	# indicate an error.  The response element contains the full XML response,
	# for debugging purposes.
	
	function send($url) {
		$this->raiseEvent("OnSend");
		
		# Build the query string, ignoring missing elements.
		$query_string = "url=".urlencode($this->url);
		if ($this->title) $query_string .= "&title=".urlencode($this->title);
		if ($this->blog) $query_string .= "&blog_name=".urlencode($this->blog);
		if ($this->data) $query_string .= "&excerpt=".urlencode($this->data);

		if (extension_loaded("curl")) {

			# Initialize CURL and POST to the target URL.
			$hnd = curl_init();
			curl_setopt($hnd, CURLOPT_URL, $url);
			curl_setopt($hnd, CURLOPT_POST, 1);
			curl_setopt($hnd, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($hnd, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($hnd, CURLOPT_POSTFIELDS, $query_string);
			$response = curl_exec($hnd);

		} else {
			
			$url_bits = parse_url($url);
			$host = $url_bits['host'];
			$path = $url_bits['path'];
			$port = isset($url_bits['port']) ? $url_bits['port'] : 80;

			# Open a socket.
			$fp = pfsockopen($host, $port);
			if (!$fp) {
				return false;
			}
			
			# Create the HTTP request to be sent to the remote host.
			$data = "POST ".$path."\r\n".
			        "Host: ".$host."\r\n".
			        "Content-Type: application/x-www-form-urlencoded; ".
		   	        "charset=utf-8\r\n".
					"Content-Length: ".strlen($query_string)."\r\n".
					"Connection: close\r\n\r\n".
		      	  $query_string;

			# Send the data and then get back any response.
			fwrite($fp, $data);
			$response = '';
	
			while (! feof($fp)) {
				$response .= fgets($fp);
			}
			fclose($fp);

		}

		# Get the error code
		$start_tag_pos = strpos($response, "<error>");
		$end_tag_pos = strpos($response, "</error>");
		if ($start_tag_pos && $end_tag_pos) {
			$ret_code = substr($response, 
			                   $start_tag_pos + strlen("<error>"),
			                   $end_tag_pos - ($start_tag_pos + strlen("<error>")) );
		} else {
			$ret_code = 1;
		}
						   
		$start_tag_pos = strpos($response, "<message>");
		$end_tag_pos = strpos($response, "</message>");
		if ($start_tag_pos && $end_tag_pos) {
			$ret_msg = substr($response, 
			                  $start_tag_pos + strlen("<message>"),
			                  $end_tag_pos - ($start_tag_pos + strlen("<message>")) );
		} elseif ($ret_code != 0) {
			$ret_msg = _('Malformed response');
		} else {
			$ret_msg = '';
		}
		
		$this->raiseEvent("SendComplete");
		return array('error'=>$ret_code, 'message'=>$ret_msg,
		             'response'=>htmlentities($response));
	}

	# Method: receive
	# Receive a TrackBack ping and store the data in a file.
	# This method also outputs a response in XML for the pinger.
	#
	# Returns:
	# Zero on success, 1 on failure.  Note that these are the same return 
	# codes described in the TrackBack specificaiton.

	function receive() {
		$this->raiseEvent("OnReceive");
		$this->getPostData();
		$parent = $this->getParent();
		$error = '';
		if (! $this->url) {
			$error = _("No URL in ping.");
		} elseif (! $parent->allow_tb) {
			$error = _("This entry does not accept trackbacks.");
		} else {
			$ts = time();
			$this->ping_date = date("Y-m-d H:i:s T", $ts);
			$path = mkpath($parent->localpath(), ENTRY_TRACKBACK_DIR, 
			               $ts.TRACKBACK_PATH_SUFFIX);
			$ret = $this->writeFileData($path);
			if (! $ret) $error = _("Unable to save ping data.");
		}
		$err_code = $error == '' ? "0" : "1";
		$output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
		          "<response>\n".
		          "<error>".$err_code."</error>\n";
		if ($error != '') $output .= "<message>$error</message>\n";
		$output .= "</response>\n";
		ob_start();
		print_r($this);
		$output .= ob_get_contents();
		ob_end_clean();

		$this->raiseEvent("ReceiveComplete");
		return $output;
	}

	# Method: incomingPing
	# Determines if there is a trackback ping in the POST data.
	#
	# Returns:
	# True if there is a ping URL in the POST, false otherwise.

	function incomingPing() {
		if (POST("url")) return true;
		else return false;
	}

	# Method: readFileData
	# Reads trackback ping data from a file.
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
				case 'URL':
					$this->url = $dat;
					break;
				case 'Title':
					$this->title = $dat;
					break;
				case 'IP':
					$this->ip = $dat;
					break;
				case 'Date':
					$this->ping_date = $dat;
					break;
				case 'Blog':
					$this->blog = $dat;
					break;
				default:
					$this->data .= $line;
			}
		}
	}

	# Method: writeFileData
	# Write trackback data to a file.
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
		$data = "URL: ".$this->url."\n".
		        "Date: ".$this->ping_date."\n".
		        "IP: ".$this->ip."\n".
		        "Title: ".$this->title."\n".
		        "Blog: ".$this->blog."\n".
		        $this->data;
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
		$tpl = NewTemplate(TRACKBACK_TEMPLATE);
		$anchor = $this->getAnchor();
		$del_link = $this->uri("delete");

		$this->control_bar = array();
		$this->control_bar[] = 
			'<a href="'.$del_link.'" '.
			#'onclick="return (window.confirm(\'Delete '.$anchor.'?\')) && this.href = this.href + \'&amp;confirm=yes\');">'
			'onclick="return window.confirm(\'Delete '.$anchor.'?\');">'
			._("Delete").'</a>';
		
		$this->raiseEvent("OnOutput");
		$tpl->set("SHOW_EDIT_CONTROLS", $SYSTEM->canModify($this->getParent(), $u) && $u->checkLogin() );
		$tpl->set("TB_URL", $this->url);
		$tpl->set("CONTROL_BAR", $this->control_bar);
		$tpl->set("TB_PERMALINK", $this->permalink());
		$tpl->set("TB_ANCHOR", $this->getAnchor());
		if ($this->ping_date) $tpl->set("TB_DATE", $this->ping_date);
		if ($this->ip) $tpl->set("TB_IP", $this->ip);
		if ($this->title) $tpl->set("TB_TITLE", $this->title);
		if ($this->blog) $tpl->set("TB_BLOG", $this->blog);
		if ($this->data) $tpl->set("TB_DATA", $this->data);
		
		$this->raiseEvent("OutputComplete");
		return $tpl->process();
	}
	
	# Method: delete
	# Permanently delete a trackback.
	#
	# Returns:
	# True on success, false on failure.
	
	function delete() {
		$this->raiseEvent("OnDelete");
		if (file_exists($this->file)) {
			$fs = NewFS();
			$ret = $fs->delete($this->file);
			$fs->destruct();
		} else $ret = false;
		$this->raiseEvent("DeleteComplete");
		return $ret;
	}

	# Method: permalink
	# Gives the permalink to the trackback entry.
	#
	# Returns:
	# A permalink to the trackback entry.
	
	function permalink() {
		return $this->uri("permalink");
	}

	# Method: getAnchor
	# Gets an anchor to the entry on the page.
	#
	# Returns:
	# The anchor to use for this trackback.
	
	function getAnchor() {
		$ret = basename($this->file);
		$ret = preg_replace("/.\w\w\w$/", "", $ret);
		$ret = "trackback".$ret;
		return $ret;
	}

	# Method: getFilename
	# Converts an anchor from getAnchor into a filename.
	#
	# Parameters:
	# anchor - The anchor to turn into a filename.
	#
	# Returns:
	# The name of the trackback file.
	
	function getFilename($anchor) {
		$ent = NewBlogEntry();
		$ret = substr($anchor, 9);
		$ret .= TRACKBACK_PATH_SUFFIX;
		$ret = mkpath($ent->localpath(),ENTRY_TRACKBACK_DIR,$ret);
		$ret = realpath($ret);
		return $ret;
	}

}
?>
