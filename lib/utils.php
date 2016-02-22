<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005-2011 Peter A. Geer <pageer@skepticats.com>

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

# File: Utilities
# Utility library.  Implements some general purpose functions plus some
# wrappers that implement functionality available in PHP 5.

# Types of directories to create.
define("BLOG_BASE", 0);
define("BLOG_ENTRIES", 1);
define("YEAR_ENTRIES", 2);
define("MONTH_ENTRIES", 3);
define("ENTRY_BASE", 4);
define("ENTRY_COMMENTS", 5);
define("ARTICLE_BASE", 6);
define("BLOG_ARTICLES", 7);
define("ENTRY_TRACKBACKS", 8);
define("ENTRY_DRAFTS", 9);
define("ENTRY_PINGBACKS", 10);
define("BLOG_DRAFTS", 11);

# Types of files for use with the getlink() function.
# For convenience, these are defined as the directories under a theme.
define("LINK_IMAGE", "images");
define("LINK_STYLESHEET", "styles");
define("LINK_SCRIPT", "scripts");

# Function: create_uri_object
# Gets the appropriate URI generator class for a given object and type.  
# Basically, this the lazy dynamic programmer's version of a factory pattern.
#
# Parameters:
# object - The object for which to get a URI generator.  The class of the object
#          determines the class returned.  Note that this function supports 
#          one level of inheritance, i.e. if the class doesn't have a URI class
#          of its own, the parent class's is used.
# type   - Optional boolean that determines what kind of URI we're using, e.g.
#          querystring, htaccess, or wrapper scripts.  If not specified, then
#          the value of the URI_TYPE configuration constant is used.
#
# Returns:
# A URI generator object.  The exact object type is determined by the parameters
# to this function call.
function create_uri_object(&$object, $type=false) {
	$objtype = get_class($object);
	$partype = get_parent_class($object);
	if (!$type) {
		$type = URI_TYPE;
	}
	
	switch ($type) {
		case "hybrid":
			$type_ext = "URIHybrid";
			break;
		case "querystring":
			$type_ext = "URIQueryString";
			break;
		case "htaccess":
			$type_ext = "URIhtaccess";
			break;
		default:
			$type_ext = "URIWrapper";
	}
	
	# If the object class and URI type is not available, try the parent class.
	$creator = $objtype.$type_ext;
	if (! class_exists($creator)) {
		$creator = $partype.$type_ext;
	}
	
	# Neither the object nor parent class have the selected URI type, try
	# dropping back to the default URI type.
	$default_ext = "URIWrapper";
	if (! class_exists($creator)) {
		$creator = $objtype.$default_ext;
	}
	if (! class_exists($creator)) {
		$creator = $partype.$default_ext;
	}
	
	return new $creator($object);
}

# Function: write_file
# Write contents to a file.  Basically a wrapper around fopen and fwrite.
# This function was rewritten to use the FS abstract interface.  For 
# one-off writes, this is fine, but for writing multiple files in the
# same call, the FS interface should be used directly to avoid overhead.
#
# Parameters:
# path     - The path to write to.
# contents - A string to write to the file.
#
# Returns: 
# Passes on the return value of the fs->write_file() method.

function write_file($path, $contents) {
	$fs = NewFS();
	$ret = $fs->write_file($path, $contents);
	return $ret;
}

# Function: mkdir_rec
# Makes directory recursively.
# As with <write_file>, is a wrapper around the FS interface.  Same deal.
#
# Parameters: 
# path - The path to create.
# mode - The *optional* octal directory permissions for the path.
#
# Returns:
# Passes on the return value of the underlying class method.

function mkdir_rec($path, $mode=false) {
	$fs = NewFS();
	# Only pass mode if it is specified.  This lets us default to the
	# appropriate default mode for the concrete subclass of FS, which is
	# different between, say, FTPFS and NativeFS.
	if ($mode) $ret = $fs->mkdir_rec($path, $mode);
	else $ret = $fs->mkdir_rec($path);
	return $ret;
}

# Function: scan_directory
# Does essentially the same thing as scandir on PHP 5.  Gets all the entries
# in the given directory.
#
# Parameters:
# path      - The directory path to scan.
# dirs_only - *Optional* parameter to list only the directories in the path.  
#             The *default* is false.
#
# Returns: 
# An array of directory entry names, removing "." and ".." entries.

function scan_directory($path, $dirs_only=false) {
	if (! is_dir($path)) return array();
	$dirhand = opendir($path);
	$dir_list = array();
	while ( false !== ($ent = readdir($dirhand)) ) {
		if ($ent != "." && $ent != "..") {
			if (! $dirs_only) $dir_list[] = $ent;
			else if (is_dir($path.PATH_DELIM.$ent)) $dir_list[] = $ent;
		}
	}
	closedir($dirhand);
	return $dir_list;
}

# Function: calculate_document_root
# An alternate way to find the document root.  This one works by comparing
# the current URL on the server to the current directory.  The idea is that
# we can find the location of the current URL in the path and remove it to
# get the document root.  Note that this function IS case-sensitive.
#
# Returns:
# The calculated document root path.

function calculate_document_root() {

	# Bail out if DOCUMENT_ROOT is already defined.
	if ( defined("DOCUMENT_ROOT") ) return DOCUMENT_ROOT;

	# Get the current URL and the path to the file.
	$curr_uri = current_uri();
	$curr_file = getcwd().PATH_DELIM.basename($curr_uri);
	if (! file_exists($curr_file)) $curr_file = getcwd();
	if (PATH_DELIM != "/") $curr_uri = str_replace("/", PATH_DELIM, $curr_uri);

	if ( preg_match(URI_TO_LOCALPATH_MATCH_RE, $curr_uri) ) {
		$curr_uri = preg_replace(URI_TO_LOCALPATH_MATCH_RE, URI_TO_LOCALPATH_REPLACE_RE,$curr_uri);
	}

	# Find the location 
	$pos = strpos($curr_file, $curr_uri);
	while (! $pos && strlen($curr_uri) > 1) {
		$curr_uri = dirname($curr_uri);
		$pos = strpos($curr_file, $curr_uri);
	}
	return substr($curr_file, 0, $pos + 1);
	
}

function calculate_server_root($path, $assume_subdomain=false) {

	$ret = '';

	if (defined("SUBDOMAIN_ROOT") && defined("DOCUMENT_ROOT")) {

		# If the path doesn't start with either the subdomain or document
		# root, then something is very, very wrong, so we need to bail 
		# the hell out and do it loud!
		if ( ! (strpos($path, DOCUMENT_ROOT)  === 0 ||
		        strpos($path, SUBDOMAIN_ROOT) === 0) ) {
			echo "Bad file passed to calculate_server_root() in ".__FILE__.
			     ".  The path '".$path."' is not under the document root (".
				  DOCUMENT_ROOT.") or the subdomain root (".SUBDOMAIN_ROOT.
				  ").  Cannot get server root.";
			return false;
		}

		# Case 1 - The document root and subdomain root are the same.
		if (SUBDOMAIN_ROOT == DOCUMENT_ROOT) {

			$ret = DOCUMENT_ROOT;

		# Case 2 - The document root is inside subdomain root.
		} elseif (strpos(DOCUMENT_ROOT, SUBDOMAIN_ROOT) === 0) {
			
			# If the path contains the document root, assume that we're NOT 
			# in a subdomain.
			$ret = ( strpos($path, DOCUMENT_ROOT) === 0) ? 
			       DOCUMENT_ROOT: SUBDOMAIN_ROOT;

		# Case 3 - The subdomain root is inside the document root.
		} elseif (strpos(SUBDOMAIN_ROOT, DOCUMENT_ROOT) === 0) {

			# If the path is in the document root, but not the subdomain
			# root, then we're definitely not in a subdomain.
			if (strpos($path, DOCUMENT_ROOT) === 0 &&
			    strpos($path, SUBDOMAIN_ROOT) === false) {
				$ret = DOCUMENT_ROOT;
			} else {
				# Otherwise, there's no way to tell if the directory is a subdomain
				# without hitting the network, so just pass the decision to the caller.
				$ret = $assume_subdomain ? SUBDOMAIN_ROOT : DOCUMENT_ROOT;
			}

		# Case 4 - The two directories are independent.
		} else {
			# If path is under the document, return that.  Otherwise, return the 
			# subdomain root.  This ends up the same as case 2.
			$ret = ( strpos($path, DOCUMENT_ROOT) === 0) ? 
			       DOCUMENT_ROOT: SUBDOMAIN_ROOT;
		}

	} else {
		$ret = calculate_document_root();
	}

	return $ret;

}

# Function: test_server_root
# Tests a root-relative path against the document root and subdomain root 
# directory and determines which one "works."
#
# Parameters:
# path - The root-relative path to test.
# assume_subdomain - Optional boolean which, when set to true, tells the routine
#                    to assume a subdomain path when the test results are 
#                    ambiguous.  Defaults to *false*.

function test_server_root($path, $assume_subdomain=false) {
	$ret = false;
	$doc_path = mkpath(calculate_document_root(), $path);
	if (defined("SUBDOMAIN_ROOT")) {
		$sub_path = mkpath(SUBDOMAIN_ROOT, $path);
		if ($doc_path == $sub_path) {
			$ret = $doc_path;
		} elseif ( file_exists($doc_path) && file_exists($sub_path) ) {
			$ret = $assume_subdomain ? $sub_path : $doc_path;
		} elseif ( file_exists($doc_path) && ! file_exists($sub_path) ) {
			$ret = $doc_path;
		} elseif ( ! file_exists($doc_path) && file_exists($sub_path) ) {
			$ret = $sub_path;
		} else {
			$ret = false;
		}
	} else {
		if (file_exists($doc_path)) $ret = $doc_path;
	}
	return $ret;
}

# Function: SESSION
# A wrapper for the $_SESSION superglobal.  Provides a handy 
# way to avoid undefined variable warnings without explicitly calling
# isset() or empty() every time.
#
# Parameters:
# key - The key for the superglobal index.
# val - The *optional* value to set to the superglobal.
#
# Returns:
# The value of the superglobal at index key, false if index is not set.

function SESSION($key, $val="") {
	if ($val) return $_SESSION[$key] = $val;
	elseif (isset($_SESSION[$key])) return $_SESSION[$key];
	else return false;
}

# Function: SERVER
#
# Parameters:
# key - The key for the superglobal index.
# val - The *optional* value to set to the superglobal.
#
# Returns:
# The value of the superglobal at index key, false if index is not set.

function SERVER($key, $val="") {
	if ($val) return $_SERVER[$key] = $val;
	elseif (isset($_SERVER[$key])) return $_SERVER[$key];
	else return false;
}

# Function: COOKIE
#
# Parameters:
# key - The key for the superglobal index.
# val - The *optional* value to set to the superglobal.
#
# Returns:
# The value of the superglobal at index key, false if index is not set.

function COOKIE($key, $val="") {
	if ($val) return $_COOKIE[$key] = $val;
	elseif (isset($_COOKIE[$key])) {
		if (get_magic_quotes_gpc()) return stripslashes($_COOKIE[$key]);
		else return $_COOKIE[$key];
	} else return false;
}
	
# Function: POST
#
# Parameters:
# key - The key for the superglobal index.
# val - The *optional* value to set to the superglobal.
#
# Returns:
# The value of the superglobal at index key, false if index is not set.

function POST($key, $val="") {
	if ($val) return $_POST[$key] = $val;
	elseif (isset($_POST[$key])) {
		if (get_magic_quotes_gpc()) return stripslashes($_POST[$key]); 
		else return $_POST[$key];
	} else return false;
}

# Function: GET
#
# Parameters:
# key - The key for the superglobal index.
# val - The *optional* value to set to the superglobal.
#
# Returns:
# The value of the superglobal at index key, false if index is not set.

function GET($key, $val="") {
	if ($val) return $_GET[$key] = $val;
	elseif (isset($_GET[$key])) {
		if (get_magic_quotes_gpc()) return stripslashes($_GET[$key]); 
		else return $_GET[$key];
	} else return false;
}

# Function: has_post
# Determine if there is any POST data.
#
# Returns:
# True if $_POST has any members, false otherwise.

function has_post() {
	return count($_POST);
}

# Function: current_uri
# Convenience function to return the path to the current script.  
# Useful for the action attribute in form tags.
#
# Parameters:
# relative     - *Optionally* get the URI relative to the current directory, i.e.
#                just the file name.  *Default* is false.
# query_string - Query string to append to URI.  *Default* is  false, which mean
#                that the current query string will be used.
# no_escape    - Indicates whether or not ampersands in the query string should
#                be escaped as &amp;, which is needed for XHTML compliance.
#
# Returns:
# A string with the URI.

function current_uri ($relative=false, $query_string=false, $no_escape=false) {
	$ret = SERVER("SCRIPT_NAME"); 
	if ($relative) $ret = basename($ret);
	if ($query_string) {
		$ret .= "?".$query_string;
	} elseif ($query_string === false && isset($_SERVER['QUERY_STRING']) ) {
		if ($no_escape) $ret .= "?".$_SERVER["QUERY_STRING"];
		else $ret .= "?".str_replace("&", "&amp;", $_SERVER["QUERY_STRING"]);
	}
	return $ret;
}

# Function: current_file
# An alias for current_uri(true, false, $no_escape).

function current_file($no_escape=false) {
	return current_uri(true, false, $no_escape);
}

# Function: current_url
#
# Returns:
# The absolute URL of the requested script.

function current_url() {
	$path = SERVER("PHP_SELF");
	# Add the protocol and server.
	$protocol = "http";
	if (SERVER("HTTPS") == "on") $protocol = "https";
	$host = SERVER("SERVER_NAME");
	$port = SERVER("SERVER_PORT");
	if ($port == 80 || $port == "") $port = "";
	else $port = ":".$port;
	return $protocol."://".$host.$port.$path;
}

# Function: localpath_to_uri
# Convert a local path to a URI.
#
# Parameters:
# path     - The path to convert.
# full_uri - *Option* to return the full URI, as opposed to just a
#            root-relative URI.  *Defaults* to true.
#
# Returns:
# A string with the URL oc the given path.

function localpath_to_uri($path, $full_uri=true, $https=false) {

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

# Function: uri_to_localpath
# The reverse of <localpath_to_uri>, this function takes a URI handled by LnBlog
# and converts it into a local path to the file or directory in question.  This
# function assumes that the URI is fully qualified, e.g. 
# |http://somehost.com/somepath/somefile.ext
# Note that this may or may not play well with Apache .htaccess files.
#
# Parameters:
# uri - The URI to convert.
#
# Returns:
# A string containing the local path referenced by the URI.  Not that this path
# may or may not exist.

function uri_to_localpath($uri) {

	$url_bits = parse_url($uri);
	if (! $url_bits) return '';
	
	#$protocol = isset($url_bits['scheme']) ? $url_bits['scheme'] : '';
	#$domain = isset($url_bits['host']) ? $url_bits['host'] : '';
	$path = isset($url_bits['path']) ? $url_bits['path'] : '';
		
	# Account for user home directories in path.  Please note that this is 
	# an ugly, ugly hack to make this function work when I'm testing on my
	# local workstation, where I use ~/www for by web root.
	if ( preg_match(URI_TO_LOCALPATH_MATCH_RE, $path) ) {
		$path = preg_replace(URI_TO_LOCALPATH_MATCH_RE, URI_TO_LOCALPATH_REPLACE_RE, $path);
	}
	
	if (defined("DOMAIN_NAME") && defined("SUBDOMAIN_ROOT") &&
		isset($url_bits['host']) && 
		preg_match('/'.str_replace('.','\.',DOMAIN_NAME).'$/', DOMAIN_NAME) &&
		strpos($url_bits['host'], DOMAIN_NAME) > 1) {
			
		$pos = strpos($url_bits['host'], DOMAIN_NAME);
		$tmp_path = substr($url_bits['host'], 0, $pos - 1);
		$path = mkpath(SUBDOMAIN_ROOT, $tmp_path, $path);
	} else {
		$path = mkpath(DOCUMENT_ROOT, $path);
	}
	
	$p = new Path($path);
	return $p->getCanonical();
	
}

# Function: get_ip
# Quick function to get the user's IP address.  This should probably be
# extended to account for proxies and such.
#
# Returns: 
# The numeric IP address of the client.

function get_ip() {
	if (isset($_SERVER['REMOTE_ADDR'])) return $_SERVER["REMOTE_ADDR"];
	else return '127.0.0.1';
}

# Returns the string to use for a config.php file.  The levels parameter
# indicates how many levels down the directory containing this script is 
# from the blog root.  Note that this should account for situations where
# the entries or content directory is set to the empty string, although I 
# don't think anything else accounts for this yet.

function config_php_string($levels) {
	$ret = 'if (! defined("PATH_SEPARATOR") ) define("PATH_SEPARATOR", strtoupper(substr(PHP_OS,0,3)==\'WIN\')?\';\':\':\');'."\n";
	$ret .= 'if (! defined("BLOG_ROOT")) define("BLOG_ROOT", dirname(__FILE__)';
	if ($levels > 0) {
		$ret .= ".'";
		for ($i = 1; $i <= $levels; $i++) {
			$ret .= DIRECTORY_SEPARATOR.'..';
		}
		$ret .= "'";
	}
	$ret .= ");\n";
	$ret .= 'require_once(BLOG_ROOT.DIRECTORY_SEPARATOR."pathconfig.php");'."\n";
	$ret .= 'require_once(INSTALL_ROOT.DIRECTORY_SEPARATOR."blogconfig.php");'."\n";
	return $ret;
}

function pathconfig_php_string($inst_root, $inst_url, $blog_url) {
	$config_data = "<?php\n";
	$config_data .= '@define("INSTALL_ROOT", \''.$inst_root."');\n";
	$config_data .= '@define("INSTALL_ROOT_URL", \''.$inst_url."');\n";
	$config_data .= '@define("BLOG_ROOT_URL", \''.$blog_url."');\n";
	#$config_data .= 'ini_set(\'include_path\', ini_get(\'include_path\').PATH_SEPARATOR.INSTALL_ROOT);';
	return $config_data;
}

# Create the required wrapper scripts for a directory.  Note that
# the instpath parameter is for the software installation path 
# and is only required for the BLOG_BASE type, as it is used 
# to create the config.php file.
# As of version 0.7.4, this function returns an array of paths for
# which the file operation returned an error code.

function create_directory_wrappers($path, $type, $instpath="") {

	$fs = NewFS();
	$blog_templ_dir = "BLOG_ROOT.'".PATH_DELIM.BLOG_TEMPLATE_DIR."'";
	$sys_templ_dir = "INSTALL_ROOT.'".PATH_DELIM.BLOG_TEMPLATE_DIR."'";

	if (! is_dir($path)) $ret = $fs->mkdir_rec($path);

	$current = $path.PATH_DELIM;
	$parent = dirname($path).PATH_DELIM;
	$config_level = 0;
	$ret = 0;
	$ret_list = array();

	switch ($type) {
		case BLOG_BASE:
			#var_dump(is_dir(realpath($instpath)), is_dir('C:\\inetpub\\wwwroot\\lnblog'), $instpath);
			if (!is_dir($instpath)) return false;
			$filelist = array("index"=>"pages/showblog");
			$removelist = array("new", "newart", "edit", "login", "logout", 
			                    "uploadfile", "map", "useredit", "plugins",
			                    "tags", "pluginload", "profile");
			$config_level = 0;
			if (! file_exists($current."pathconfig.php")) {
				$inst_root = realpath($instpath);
				$blog_root = realpath($path);
				#var_dump($inst_root, $blog_root);
				$inst_url = localpath_to_uri($inst_root);
				$blog_url = localpath_to_uri($blog_root);
				$config_data = pathconfig_php_string($inst_root, $inst_url, $blog_url);
				$ret = $fs->write_file($current."pathconfig.php", $config_data);
				if (! $ret) $ret_list[] = $current."pathconfig.php";
			}
			break;
		case BLOG_ENTRIES:
			$filelist = array("index"=>"pages/showarchive");
			$removelist = array("all");
			$config_level = 1;
			break;
		case BLOG_DRAFTS:
			$filelist = array("index"=>"pages/showdrafts");
			$config_level = 1;
			break;
		case YEAR_ENTRIES:
			$filelist = array("index"=>"pages/showarchive");
			$config_level = 2;
			break;
		case MONTH_ENTRIES:
			$filelist = array("index"=>"pages/showarchive");
			$removelist = array("day");
			$config_level = 3;
			break;
		case ENTRY_BASE:
			$filelist = array("index"=>"pages/showitem");
			$removelist = array("edit", "delete", "trackback", "uploadfile");
			$config_level = 4;
			break;
		case ENTRY_COMMENTS:
			$filelist = array("index"=>"pages/showitem");
			$removelist = array("delete");
			$config_level = strtolower($instpath) == 'article' ? 3 : 5;
			break;
		case ENTRY_TRACKBACKS:
			$filelist = array("index"=>"pages/showitem");
			$config_level = strtolower($instpath) == 'article' ? 3 : 5;
			break;
		case ENTRY_PINGBACKS:
			$filelist = array("index"=>"pages/showitem");
			$config_level = strtolower($instpath) == 'article' ? 3 : 5;
			break;
		case ARTICLE_BASE:
			# The same as for entries, but for some reason, I never added a delete.
			$filelist = array("index"=>"pages/showitem");
			$removelist = array("edit", "trackback", "uploadfile", "trackback");
			$config_level = 2;
			break;
		case BLOG_ARTICLES:
			$filelist = array("index"=>"pages/showarticles");
			$config_level = 1;
			break;
		case ENTRY_DRAFTS:
			$filelist = array("index"=>"pages/showdrafts");
			$config_level = 1;
			break;
	}
	# Write the config.php file, with the appropriate level of distance
	# from the blog root.
	#$ret = $fs->write_file($current."config.php", 
	#                       config_php_string($config_level));
	#if (! $ret) $ret_list[] = $current."config.php";

	foreach ($filelist as $file=>$content) {
		$curr_file = $current.$file.".php";
		$body = "<?php\n";
		$body .= config_php_string($config_level);
		$body .= "include(INSTALL_ROOT.DIRECTORY_SEPARATOR.\"".
			str_replace('/', DIRECTORY_SEPARATOR, $content).".php\");\n";

		$ret = $fs->write_file($curr_file, $body);
		if (! $ret) $ret_list[] = $curr_file;
	}

	if (file_exists($current."config.php")) {
		$fs->delete($current."config.php");
	}

	if (isset($removelist) && is_array($removelist)) {
		foreach ($removelist as $file) {
			$f = $current.$file.".php";
			if (file_exists($f)) {
				$fs->delete($f);
			}
		}
	}

	return $ret_list;
}

# Function: getlink
# Returns a path suitable for use with <link> tags or as an href or src 
# attribute.  Takes a file name and optional type (script, image, or style 
# sheet) and returns a URI.  If the type is not given, the function guesses 
# it from the file extension.
#
# Parameters:
# name - The filename of the item to link.
# type - The *optional* type of the item.  May be one of the defined 
#        constants LINK_IMAGE, LINK_SCRIPT, of LINK_STYLESHEET.
#
# Returns:
# The root-relative path to the item.  If no item is found in any path, 
# returns the name parameter as-is.

function getlink($name, $type=false) {
	if ($type) {
		$l_type = $type;
	} else {
	
		# Extract the file extension, i.e. the part after the last '.' 
		$file_ext = strtolower(substr($name, strpos($name, ".") + 1));

		# Choose a type based on extension.  This is the place to add any
		# extra extensions you need to support.  I don't know what the 
		# performance penalty for that might be, but it's probably small.
		switch ($file_ext) {
			case "jpg":
			case "jpeg":
			case "png":
			case "gif":
			case "bmp":
			case "tif":
			case "tiff":
				$l_type = LINK_IMAGE;
				break;
			case "js":
			case "vbs":
				$l_type = LINK_SCRIPT;
				break;
			case "css":
			case "xsl":
				$l_type = LINK_STYLESHEET;
				break;
		}
	}

	# If we didn't find a suitable file type, then bail out.
	if (! $l_type) return false;

	# Find the file using a search-path metaphor.  This isn't actually 
	# configurable, though.
	
	$blog = NewBlog();
	
	# Check the blog directory
	if ( defined("BLOG_ROOT") && 
	     file_exists(BLOG_ROOT.PATH_DELIM.$l_type.PATH_DELIM.$name) ) {
		
		$ret = $blog->uri('base').$l_type."/".$name;
		
	# Try the userdata directory
	} elseif ( file_exists(USER_DATA_PATH.PATH_DELIM."themes".PATH_DELIM.THEME_NAME.
	                       PATH_DELIM.$l_type.PATH_DELIM.$name) ) {
		$ret = INSTALL_ROOT_URL.USER_DATA."/themes/".THEME_NAME."/".$l_type."/".$name;

	# Check the current theme directory
	} elseif ( file_exists(INSTALL_ROOT.PATH_DELIM."themes".PATH_DELIM.THEME_NAME.
	                       PATH_DELIM.$l_type.PATH_DELIM.$name) ) {
		$ret = INSTALL_ROOT_URL."themes/".THEME_NAME."/".$l_type."/".$name;

	# Try the default theme
	} elseif ( file_exists(
	             mkpath(INSTALL_ROOT,"themes","default",$l_type,$name) ) ) {
		$ret = INSTALL_ROOT_URL."themes/default/".$l_type."/".$name;

	# Last case: nothing found, so return the original string.
	} else {
		$ret = $name;
	}
	
	$urlinfo = parse_url($ret);
	if (isset($urlinfo['host']) && 
	    $l_type == LINK_SCRIPT &&
	    SERVER("SERVER_NAME") != $urlinfo['host']) {
		$ret = $blog->uri('script', $name);
	}
	
	return $ret;
}

# Function: sanitize
# Strips the input of certain characters.  This is a thin wrapper around
# the preg_replace function.
# 
# Parameters:
# str     - The string to strip.
# pattern - The pattern to match.  *Default* is /\W/, i.e. non-word chars.
# sub     - The substitution string for pattern, *default* is null string.
#
# Returns:
# The string with all characters matching sub replaced by the 
# sub character.

function sanitize($str, $pattern="/\W/", $sub="") {
	if (! $str) return "";
	if (preg_match($pattern, $str)) {
		return preg_replace($pattern, $sub, $str);
	} else {
		return $str;
	}
}

# Function: in_arrayi
# Like in_array() standard function, except case-insensitive
# for strings.

function in_arrayi($needle, $haystack) {
	if (! is_array($haystack)) return false;
	$lcase_needle = strtolower($needle);
	foreach ($haystack as $val) 
		if (strtolower($val) == $lcase_needle) return true;
	return false;
}

# Function: make_uri
# Builds a correct URI with query string.  This is an all-purpose kind of 
# function designed to handle most contingencies.  
#
# Parameters:
# base         - The base URI to use.  If not given, the current URI is used.
# query_string - An associative array representing the query string.
# no_get       - A boolean set to true to suppress automatic importing of the
#                $_GET superglobal into the query_string parameter.
# link_sep     - Character to separate query string parameters.  *Default*
#                value is '&amp;' for link hrefs.  Use '&' for redirects.
# add_host     - If set to true, adds a host and protocol to the returned
#                URI if they are not already present.
#
# Returns:
# A string containing the resulting URI.

function make_uri($base=false, $query_string=false, $no_get=true, 
                  $link_sep='&amp;', $add_host=false) {
	if ($no_get) $qs = array();
	else $qs = $_GET;
	
	if ($query_string) $qs = array_merge($qs, $query_string); 
	
	if ( ! $base ) $base = $_SERVER['SCRIPT_NAME'];
	
	$qs_start_pos = strpos($base, '?');
	if ($qs_start_pos !== false) {
		$url_parms = substr($base, $qs_start_pos+1);
		$base = substr($base, 0, $qs_start_pos);
		$url_parms = str_replace('&amp;', '&', $url_parms);
		$parms = explode('&', $url_parms);
		foreach ($parms as $p) {
			$tmp = explode('=', $p);
			$qs[$tmp[0]] = $tmp[1];
		}
	}
	
	$ret = '';
	if (count($qs) > 0) {
		foreach ($qs as $key=>$val) {
			if ($ret) $ret .= $link_sep.$key.'='.$val;
			else      $ret = $key.'='.$val;
		}
		$ret = "?".$ret;
	}
	
	# Note that it's OK to not check for ===false because we don't want the 
	# needle to be at the beginning of the string, so 0 is unacceptable.
	if ( ! strpos($base, "://") && $add_host) {
		$protocol = "http";
		if (SERVER("HTTPS") == "on") $protocol = "https";
		$host = SERVER("SERVER_NAME");
		$port = SERVER("SERVER_PORT");
		if ($port == 80 || $port == "") $port = "";
		else $port = ":".$port;
		if (substr($base,0,1) != '/') $base = '/'.$base;
		$base = $protocol."://".$host.$port.$base;
	}
	
	$ret = $base.$ret;
	
	return $ret;
}

# Function: set_domain_cookie
# A convenience wrapper for the setcookie() function.  If the DOMAIN_NAME
# constant is set, then this will set the cookie for .domain.whatever, but will
# simply skip the domain parameter otherwise.  This also sets the path to the
# root directory.

function set_domain_cookie($name, $value='', $expire=0) {
	if (defined('DOMAIN_NAME')) {
		setcookie($name, $value, $expire, '/', '.'.DOMAIN_NAME);
	} else {
		setcookie($name, $value, $expire, '/');
	}
}

# Function: get_entry_from_uri
# Takes a URI and converts it into an entry object.
function get_entry_from_uri($uri) {
	$local_path = uri_to_localpath($uri);

	if (is_dir($local_path)) {
		$ent = NewEntry($local_path);
		
	# If the resulting entry is a file, then see if it's a PHP wrapper script
	# for entry pretty-permalinks.
	} elseif (is_file($local_path)) {
		$dir_path = dirname($local_path);
		$content = file($local_path);
		
		$re = "/DIRECTORY_SEPARATOR.'(\d+_\d+)'.DIRECTORY_SEPARATOR/";
		
		if (preg_match($re, $content[0], $matches)) {
			$dir = $matches[1];
		} else $dir = '';
		$dir = mkpath($dir_path, $dir);
		$ent = NewEntry($dir);
		
	# In the future, we will want to add conditions for checking query strings
	# and Apache rewrite rules.
	
	# If no condition is met, then bail out with an unrecognized URI fault.
	} else {
		$ent = false;
	}
	return $ent;
}

##############################################
# Section: HTML String Convenience Functions #
##############################################

# Function: ahref
# Creates markup for a hyperlink.
#
# Parameters:
# href    - The URL to link to.
# text    - The main link text.
# attribs - An associative array of additional attributes, with the key as the
#           attribute name.
function ahref($href, $text, $attribs=false) {
	$text = htmlspecialchars($text);
	$base = "<a href=\"$href\"";
	if ($attribs) {
		foreach ($attribs as $key=>$val)
			$base .= " $key=\"$val\"";
	}
	return "$base>$text</a>";
}

