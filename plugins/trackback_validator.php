<?php

# Plugin: TrackbackValidator
# Validates TrackBacks when they are posted.
#
# This plugin implements a simple method for preventing TrackBack spam.  It 
# simply fetches the URL passed in the TrackBack ping and scans it for a link 
# to the entry the TrackBack was posted to.  If no such link is found, then the
# conclusion is that this TrackBack is spam and it is rejected.
#
# Note that there are two options for this plugin.  The first is an option to 
# exclude links to the same host as your blog from validation.  The second is 
# to check the ping URL for links to the entry base directory, in addition to
# the entry permalink.  Effectively, this means that URLs that link the the 
# entry permalink, the comments page, or a file uploaded to the entry will all
# pass validation.  Without this, only links to the "official" permalink will 
# pass.
#
# The idea for this plugin comes from the paper 
# "Taking TrackBack Back (from Spam)" by Gerecht et al. from Rice University.
# The paper is available at 
# <http://seclab.cs.rice.edu/proj/trackback/papers/taking-trackback-back.pdf>

class TrackbackValidator extends Plugin {

	function TrackbackValidator() {
		$this->plugin_desc = _('Allow only TrackBacks that link to your URL in the page body.');
		$this->plugin_version = '0.1.0';
		
		$this->addOption('allow_self', 
		                 _('Allow TrackBacks from your own blog'),
		                 true, 'checkbox');
		$this->addOption('base_uri',
		                 _('Allow pings from pages that link to anything under the entry, not just the permalink.'),
		                 true, 'checkbox');
		$this->getConfig();
	}
	
	function check_for_link(&$param) {
		$url = parse_url($param->url);
		if ( $this->allow_self && $url['host'] == SERVER("SERVER_NAME") ) {
			return true;
		}
		$ent = $param->getParent();
		$data = $this->fetch_page($param->url);
		
		# If the permalink is in the page, it's legitimate, so return true.
		if (strpos($data, $ent->permalink()) > 0) {
			return true;
		}
		
		# If we're also checking the base URI and that's in the page, then
		# we can also return true.
		# Note that this should return true even if the link is actually to the
		# comments page or a file under the entry rather than the permalink
		# wrapper script.  This may or may not be a good thing, hence the 
		# option.
		if ($this->base_uri && strpos($data, $ent->uri('base')) > 0) {
			return true;
		}
		
		$param->url = '';
		return false;
	}
	
	function fetch_page($url) {
		if (extension_loaded('curl')) {
			
			$hnd = curl_init();
			curl_setopt($hnd, CURLOPT_URL, $url);
			curl_setopt($hnd, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($hnd, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($hnd, CURLOPT_HEADER, 1);
			$response = curl_exec($hnd);
			
		} else {
			
			$url_bits = parse_url($url);
			$host = $url_bits['host'];
			$path = $url_bits['path'];
			$port = isset($url_bits['port']) ? $url_bits['port'] : 80;
			$query = isset($url_bits['query']) ? $url_bits['query'] : '';
			
			# Open a socket.
			$fp = @fsockopen($host, $port);
			if (!$fp) return false;
	
			# Create the HTTP request to be sent to the remote host.
			if ($query) $path .= '?'.$query;
			$data = "GET ".$path."\r\n".
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
	
}

$plug =& new TrackbackValidator();
$plug->registerEventHandler("trackback", "POSTRetreived", "check_for_link");
?>
