<?php
class Server {
	
	function Server () {
		$this->domain = (defined("DOMAIN_NAME") ? DOMAIN_NAME : "");
		$this->docroot = (defined("DOCUMENT_ROOT") ? DOCUMENT_ROOT : "");
		$this->subdomainroot = (defined("SUBDOMAIN_ROOT") ? SUBDOMAIN_ROOT : "");
		
		$this->path_replace = 
			array('match'=>"/^\/home\/([^\/]+)\/(www|public_html)\/(.*)/i",
			      'replace'=>"/~$1/$3");
		$this->uri_replace = array('match'=>"/^\/~([^\/]+)(.*)/i", 'replace'=>"$2");
	}
	
	function instance() {
		static $inst;
		if (! isset($inst)) $inst = new Server();
		return $inst;
	}
	
	function dirSep() {
		return DIRECTORY_SEPARATOR;
	}
	
	function pathSep() {
		if (defined('PATH_SEPARATOR')) return PATH_SEPARATOR;
	}
	
	function getSubdomainURI($subdomain=false, $https=false) {
		if ( (isset($_SERVER["HTTPS"]) && 
		      strtolower($_SERVER["HTTPS"]) == "on") || $https) {
			$protocol = "https";
		} else $protocol = "http";
		
		$port = isset($_SERVER["SERVER_PORT"]) ? $_SERVER["SERVER_PORT"] : 80;
		
		if (! is_numeric($port) || $port == 80) $port = "";
		else $port = ":".$port;
		
		if ($subdomain) {
			$host = $subdomain.".".$this->domain;
		} else {
			$host = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : '';
		}
		return $protocol."://".$host.$port;
	}

	function getURI($https=false) {
		return $this->getSubdomainURI(false, $https);
	}
	
}

class URIMapper {

	function toLocalpath($uri) {
	
	}
	
	function toUri($path) {
	
		$SERVER = Server::instance();
	
		if (file_exists($path))	$full_path = realpath($path);
		else $full_path = $path;
		
		# Add a trailing slash if the path is a directory.
		if (is_dir($full_path)) $full_path .= PATH_DELIM;
		
		$root = calculate_document_root();
		$subdom_root = '';
		if (defined("SUBDOMAIN_ROOT")) {
			$subdom_root = SUBDOMAIN_ROOT;
		}
	
		# Normalize to lower case on Windows in order to avoid problems
		# with case-sensitive substring removal.
		if ( strtoupper( substr(PHP_OS,0,3) ) == 'WIN' ) {
			$root = strtolower($root);
			$subdom_root= strtolower($subdom_root);
			$full_path = strtolower($full_path);
		}
	
		$subdomain = '';
	
		# Account for user home directories in path.  Please note that this is 
		# an ugly, ugly hack to make this function work when I'm testing on my
		# local workstation, where I use ~/www for by web root.
		if ( preg_match(LOCALPATH_TO_URI_MATCH_RE, $full_path) ) {
			#$url_path = '/~'.basename(dirname($root)).$url_path;
			$url_path = preg_replace(LOCALPATH_TO_URI_MATCH_RE, LOCALPATH_TO_URI_REPLACE_RE, $full_path);
		} elseif ($subdom_root && strpos($full_path, $subdom_root) === 0) {
			$url_path = str_replace($subdom_root, "", $full_path);
			$slashpos = strpos($url_path, PATH_DELIM);
			$subdomain = substr($url_path, 0, $slashpos);
			$url_path = substr($url_path, $slashpos + 1);
		} else {
			$url_path = str_replace($root, "", $full_path);
		}
		
		# Remove any drive letter.
		if ( strtoupper( substr(PHP_OS,0,3) ) == 'WIN' ) 
			$url_path = preg_replace("/[A-Za-z]:(.*)/", "$1", $url_path);
		# Convert to forward slashes.
		if (PATH_DELIM != "/") 
			$url_path = str_replace(PATH_DELIM, "/", $url_path);
		
		# The URI should *always* be absolute.  Therefore, we allow for the 
		# DOCUMENT_ROOT to have a slash at the end by prepending a
		# slash here if we don't already have one.
		if (substr($url_path, 0, 1) != "/") $url_path = "/".$url_path;
			
		if ($full_uri) {
			# Add the protocol and server.
			$protocol = ($https ? "https" : "http");
			if (SERVER("HTTPS") == "on") $protocol = "https";
			$host = SERVER("SERVER_NAME");
			$port = SERVER("SERVER_PORT");
			if ($port == 80 || $port == "") $port = "";
			else $port = ":".$port;
			if ($subdomain) {
				$host = $subdomain.".".DOMAIN_NAME;
			}
			$url_path = $protocol."://".$host.$port.$url_path;
		}
		return $url_path;
	}

}
