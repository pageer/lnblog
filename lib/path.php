<?php
require_once("server.php");

class Path {
	
	function Path() {
		$this->sep = DIRECTORY_SEPARATOR;
		$this->path = func_get_args();
	}
	
	function hasWindowsSep($path) {
		$c1 = chr(strtoupper(substr($path,0,1)));
		$c2 = substr($path,1,2);
		return ( $c1 >= ord("A") && $c1 <= ord("Z") && ($c2 == ':\\' || $c2 == ":" ) );
	}
	
	function isAbsolute($path) {
		switch($this->sep) {
			case '\\': 
				$c1 = ord(strtoupper(substr($path,0,1)));
				$c2 = substr($path,1,1);
				return ( $c1 >= ord("A") && $c1 <= ord("Z") && $c2 == ':');
			case '/':
				return substr($path,0,1) == '/';
		}
	}
	
	function implodePath($sep, $path) {
		$ret = implode($sep, $path);
		return str_replace($sep.$sep, $sep, $ret);
	}
	
	function get() {
		if (func_num_args() == 0) {
			return $this->implodePath($this->sep, $this->path);
		} else {
			$args = func_get_args();
			return Path::implodePath(DIRECTORY_SEPARATOR, $args);
		}
	}
	
	function getCanonical() {
		$str = $this->get();
		$components = explode($this->sep, $str);
		$ret = '';
		
		for ($i = count($components) - 1; $i > 0; $i--) {
			switch ($components[$i]) {
				case ''   : break;
				case '.'  : break;
				case '..' : $i--; break;
				default   : $ret = $this->sep.$components[$i].$ret;
			}
		}
		
		# Note that for UNIX platforms, $components[0] will be empty for
		# absolute paths.
		if ($components[0] != '.' && $components[0] != '..') {
			$ret = $components[0].$ret;
		}
		
		return $ret;
	}
	
	function urlSlashes($striproot=false) {
		$path = $this->get();
		if ($striproot && strpos($path,$striproot) === 0) {
			$path = substr($path, strlen($striproot));
		}
			
		if ($this->sep != '/') {
			if ($this->isAbsolute($path)) $path = substr($path, 2);
			$path = str_replace($this->sep, '/', $path);
		}
		return $path;
	}
	
	function stripPrefix($prefix) {
		$ret = $this->get();
		if ($this->hasPrefix($prefix)) $ret = substr($ret, strlen($prefix));
		return $ret;
	}
	
	function hasPrefix($prefix) {
		return strpos($this->get(), $prefix) === 0;
	}
	
	function append($dir) {
		$args = func_get_args();
		$this->path = array_merge($this->path, $args);
	}
	
	function appendSlash($path) {
		$server = Server::instance();
		if (substr($path, -1) != $server->dirSep()) {
			return $path.$server->dirSep();
		} else {
			return $path;
		}
	}
	
	function toURL() {
		$sys = Server::instance();

		if (file_exists($this->get())) {
			$full_path = realpath($path);
			# Add a trailing slash if the path is a directory.
			if (is_dir($full_path)) $full_path .= $s->dirSep();
		} else {
			$full_path = $this->getCanonical();
			$lastchar = substr($this->path[count($this->path)-1],
			                   strlen($this->path[count($this->path)-1]) - 1);
			if ($lastchar == '/') $full_path .= DIRECTORY_SEPARATOR;
		}
		
		$root = $this->docroot;
		$subdom_root = $this->subdomainroot;
	
		$subdomain = '';
	
		# Account for user home directories in path.  Please note that this is 
		# an ugly, ugly hack to make this function work when I'm testing on my
		# local workstation, where I use ~/www for my web root.
		if ( preg_match($sys->path_replace['match'], $full_path) ) {
			#$url_path = '/~'.basename(dirname($root)).$url_path;
			$url_path = preg_replace($sys->path_replace['match'], 
			                         $sys->path_replace['replace'],
			                         $full_path);
		} elseif ($this->subdomainroot && strpos($full_path, $sys->subdomainroot) === 0) {
			$url_path = substr($full_path, strlen($this->subdomainroot));
			$slashpos = strpos($url_path, DIRECTORY_SEPARATOR);
			$subdomain = substr($url_path, 0, $slashpos);
			$url_path = substr($url_path, $slashpos + 1);
		} else {
			$url_path = str_replace($root, "", $full_path);
		}
		
		# Remove any drive letter.
		if ( strtoupper( substr(PHP_OS,0,3) ) == 'WIN' ) 
			$url_path = preg_replace("/[A-Za-z]:(.*)/", "$1", $url_path);
		# Convert to forward slashes.
		if (DIRECTORY_SEPARATOR != "/") 
			$url_path = str_replace(DIRECTORY_SEPARATOR, "/", $url_path);
		
		# The URI should *always* be absolute.  Therefore, we allow for the 
		# DOCUMENT_ROOT to have a slash at the end by prepending a
		# slash here if we don't already have one.
		if (substr($url_path, 0, 1) != "/") $url_path = "/".$url_path;
			
		if ($full_uri) {
			# Add the protocol and server.
			$protocol = ($https ? "https" : "http");
			if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $protocol = "https";
			$host = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : '';
			$port = isset($_SERVER["SERVER_PORT"]) ? $_SERVER["SERVER_PORT"] : 80;
			if (! is_numeric($port) || $port == 80) $port = "";
			else $port = ":".$port;
			if ($subdomain) {
				$host = $subdomain.".".$this->domain;
			}
			$url_path = $protocol."://".$host.$port.$url_path;
		}
		return $url_path;
	}
}
?>