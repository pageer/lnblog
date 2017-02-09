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
