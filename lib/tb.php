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

# Class for TrackBack entries.  This attempts to comply with theTrackBack
# specification at http://www.sixapart.com/pronet/docs/trackback_spec
# This is used to recieve and send TrackBack pings as well as to access
# locally stored ping data.

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

	function Trackback($path=false) {
		$this->title = '';
		$this->blog = '';
		$this->data = '';
		$this->url = '';
		$this->ping_date = false;
		if ($path && is_file($path)) {
			$this->readFileData($path);
		}
	}

	function getPostData() {
		$this->title = htmlspecialchars(POST("title"));
		$this->data = htmlspecialchars(strip_tags(POST("excerpt")));
		$this->blog = htmlspecialchars(POST("blog_name"));
		$this->url = POST("url");	
	}

	# Send a TrackBack ping without using a form.

	function send($url) {
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
								 
		return $ret_code;
	}

	# Receive a TrackBack ping and store the data in a file.

	function receive($save_dir) {
		$this->getPostData();
		$error = '';
		if (! $this->url) {
			$error = _("No URL in ping.");
		} else {
			$ts = time();
			$this->date = date("Y-m-d H:i:s T", $ts);
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
		return $error;
	}

	function incomingPing() {
		if (POST("url")) return true;
		else return false;
	}

	function readFileData($path) {
		$file_data = file($path);
		foreach ($file_data as $line) {
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
				case 'Date':
					$this->date = $dat;
					break;
				case 'Blog':
					$this->blog = $dat;
					break;
				default:
					$this->data .= $line;
			}
		}
	}

	function writeFileData($path) {
		$fs = NewFS();
		if (! is_dir( dirname($path) ) ) {
			$fs->mkdir_rec(dirname($path));
		}
		$data = "URL: ".$this->url."\n".
		        "Date: ".$this->date."\n".
		        "Title: ".$this->title."\n".
		        "Blog: ".$this->blog."\n".
		        $this->data;
		$ret = $fs->write_file($path, $data);
		$fs->destruct();
		return $ret;
	}

	# Put the saved data into a template for display.

	function get() {
		$tpl = NewTemplate(TRACKBACK_TEMPLATE);
		$tpl->set("TB_URL", $this->url);
		if ($this->date) $tpl->set("TB_DATE", $this->date);
		if ($this->title) $tpl->set("TB_TITLE", $this->title);
		if ($this->blog) $tpl->set("TB_BLOG", $this->blog);
		if ($this->data) $tpl->set("TB_DATA", $this->data);
		return $tpl->process();
	}

}
