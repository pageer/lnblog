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
# POSTRetreived   - Fired when POST data for a trackback is retreived.
# OnInsert        - Fired before a trackback is stored.
# InsertComplete  - Fired after a trackback is saved.
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
	}

	# Method: getPostData
	# Pulls the trackback data out of the POST and into the object.

	function getPostData() {
		$this->title = htmlspecialchars(POST("title"));
		$this->data = htmlspecialchars(strip_tags(POST("excerpt")));
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
	# The trackback response code, or false on failure.

	function send($url) {
		$this->raiseEvent("OnSend");
		# Extract the host name and path from the URL.
		$proto_pos = strpos($url, "://");
		$slash_pos = strpos($url, "/", $proto_pos + 3);
		if ($proto_pos == false || $slash_pos == false) return false;
		$host = substr($url, $proto_pos + 3, $slash_pos - ($proto_pos + 3));
		$path = substr($url, $slash_pos);

		# Open a socket.
		$fp = fsockopen($host, 80);
		if (!$fp) return false;

		# Build the query string, ignoring missing elements.
		$query_string = "url=".urlencode($this->url);
		if ($this->title) $query_string .= "&title=".urlencode($this->title);
		if ($this->blog) $query_string .= "&blog_name=".urlencode($this->blog);
		if ($this->data) $query_string .= "&excerpt=".urlencode($this->data);
		
		# Create the HTTP request to be sent to the remote host.
		$data = "POST ".$path."\r\n".
		        "Content-Type: application/x-www-form-urlencoded; ".
		        "charset=utf-8\r\n\r\n".
		        $query_string;

		# Send the data and then get back any response.
		fwrite($fp, $query_string);
		$response = '';
	
		while (! feof($fp)) {
			$response .= fgets($fp);
		}
		fclose($fp);

		# Get the error code
		$start_tag_pos = strpos($response, "<error>");
		$end_tag_pos = strpos($response, "</error>");
		$ret_code = substr($response, 
		                   $start_tag_pos + strlen("<error>"),
		                   $end_tag_pos - ($start_tag_pos + strlen("<error>")) );
								 
		$this->raiseEvent("SendComplete");
		return $ret_code;
	}

	# Method: receive
	# Receive a TrackBack ping and store the data in a file.
	#
	# Parameters:
	# save_dir - The directory where the ping will be saved.
	#
	# Returns:
	# Zero on success, 1 on failure.

	function receive($save_dir) {
		$this->raiseEvent("OnReceive");
		$this->getPostData();
		$error = '';
		if (! $this->url) {
			$error = _("No URL in ping.");
		} else {
			$ts = time();
			$this->ping_date = date("Y-m-d H:i:s T", $ts);
			$ret = $this->writeFileData($save_dir.PATH_DELIM.$ts.".txt");
			if (! $ret) $error = _("Unable to save ping data.");
		}
		$err_code = $error == '' ? "0" : "1";
		$output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
		          "<response>\n".
		          "<error>".$err_code."</error>\n";
		if ($error != '') $output .= "<message>$error</message>\n";
		$output .= "</response>\n";

		echo $output;
		$this->raiseEvent("ReceiveComplete");
		return $error;
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
		$blog = NewBlog();
		$tpl = NewTemplate(TRACKBACK_TEMPLATE);
		$anchor = $this->getAnchor();
		$del_link = localpath_to_uri(dirname($this->file)).
			"?delete=".$anchor;

		$this->control_bar = array();
		$this->control_bar[] = 
			'<a href="'.$del_link.'" '.
			#'onclick="return (window.confirm(\'Delete '.$anchor.'?\')) && this.href = this.href + \'&amp;confirm=yes\');">'
			'onclick="return window.confirm(\'Delete '.$anchor.'?\');">'
			._("Delete").'</a>';
		
		$this->raiseEvent("OnOutput");
		$tpl->set("SHOW_EDIT_CONTROLS", $blog->canModifyEntry());
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
		if (file_exists($this->file)) {
			$fs = NewFS();
			$ret = $fs->delete($this->file);
			$fs->destruct();
		} else $ret = false;
		return $ret;
	}

	# Method: permalink
	# Gives the permalink to the trackback entry.
	#
	# Returns:
	# A permalink to the trackback entry.
	
	function permalink() {
		return localpath_to_uri(dirname($this->file))."#".$this->getAnchor();
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
