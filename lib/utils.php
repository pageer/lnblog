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

# File: Utilities
# Utility library.  Implements some general purpose functions plus some
# wrappers that implement functionality available in PHP 5.

require_once("lib/creators.php");


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

# Types of files for use with the getlink() function.
# For convenience, these are defined as the directories under a theme.
define("LINK_IMAGE", "images");
define("LINK_STYLESHEET", "styles");
define("LINK_SCRIPT", "scripts");

# Function: canonicalize
# Return the canonical path for a given file.  The file need no exist.
# Removes all '.' and '..' components and returns an absolute path.
#
# Parameters:
# path - The path to canoncialize.
#
# Returns:
# The canonical path to the path parameter.

function canonicalize ($path) {
	if ( file_exists($path) ) return realpath($path);
	# If the path is relative, prepend the current directory.
	if (! is_absolute($path)) $path = getcwd().PATH_DELIM.$path;
	$components = explode(PATH_DELIM, $path);
	$ret = array();
	$i = 0;
	$last_item = false;
	foreach ($components as $item) {
		if ($item == '..') $i--;
		elseif ($item != '.') $ret[$i++] = $item;
	}
	$ret = array_slice($ret, 0, $i);
	$ret = implode(PATH_DELIM, $ret);
	return $ret;
}

# Method: is_absolute
# Return whether or not a path is absolute.
#
# Parameters:
# path - The path to check.
#
# Returns:
# True ic the path is absolute, false otherwise.

function is_absolute($path) {
	if (PATH_DELIM == "/") {
		return ( substr($path, 0, 1) == "/" );
	} else {
		return ( substr($path, 1, 2) == ":\\" );
	}
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
	$fs->destruct();
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
# dirs_only - *Option* to list only the directories in the path.  
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

# Function: php_version_at_least
# Check that PHP is at least a certain version.
#
# Parameters:
# ver - The target version number in "1.2.3" format.
#
# Returns:
# True if that current PHP version is at least ver, false otherwise.

function php_version_at_least($ver) {
	$res = version_compare(phpversion(), $ver);
	if ($res < 0) return false;
	else return true;
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
	elseif (isset($_COOKIE[$key])) return $_COOKIE[$key];
	else return false;
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
	elseif (isset($_POST[$key])) return $_POST[$key];
	else return false;
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
	elseif (isset($_GET[$key])) return $_GET[$key];
	else return false;
}

# Function: GETPOST
# Like $_REQUEST.  This is the same as checking <POST> and then <GET>
# and returning the first result.
#
# Parameters:
# key - The key to return.
#
# Returns:
# The value of $_POST[$key] if it is set, else $_GET[$key].  Returns false
# if neither one is set.

function GETPOST($key) {
	if (isset($_POST[$key])) return $_POST[$key];
	elseif (isset($_GET[$key])) return $_GET[$key];
	else return false;
}

# Function: has_post
# Determine if there is any POST data.
#
# Returns:
# True if $_POST has any members, false otherwise.

function has_post() {
	return count($_POST);
}

# Function: has_post
# Determine if there is any GET data.
#
# Returns:
# True if $_GET has any members, false otherwise.

function has_get() {
	return count($_GET);
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

function localpath_to_uri($path, $full_uri=true) {

	if (file_exists($path))	$full_path = realpath($path);
	else $full_path = $path;

	# Add a trailing slash if the path is a directory.
	if (is_dir($full_path)) $full_path .= PATH_DELIM;
	$root = calculate_document_root();
	# Normalize to lower case on Windows in order to avoid problems
	# with case-sensitive substring removal.
	if ( strtoupper( substr(PHP_OS,0,3) ) == 'WIN' ) {
		$root = strtolower($root);
		$full_path = strtolower($full_path);
	}

	# Account for user home directories in path.  Please note that this is 
	# an ugly, ugly hack to make this function work when I'm testing on my
	# local workstation, where I use ~/www for by web root.
	if ( preg_match(LOCALPATH_TO_URI_MATCH_RE, $full_path) ) {
		#$url_path = '/~'.basename(dirname($root)).$url_path;
		$url_path = preg_replace(LOCALPATH_TO_URI_MATCH_RE, LOCALPATH_TO_URI_REPLACE_RE, $full_path);
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
		$protocol = "http";
		if (SERVER("HTTPS") == "on") $protocol = "https";
		$host = SERVER("SERVER_NAME");
		$port = SERVER("SERVER_PORT");
		if ($port == 80 || $port == "") $port = "";
		else $port = ":".$port;
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
	# Extract the protocol.
	$pos = strpos($uri, '://');
	$protocol = substr($uri, 0, $pos);
	$temp_uri = substr($uri, $pos+3);
	
	# Now separate the domain and path.
	$pos = strpos($uri, '/');
	$domain = substr($temp_uri, 0, $pos);
	$path = substr($temp_uri, $pos);
	
	# Check if there is a port number with the domain.
	$pos = strpos($domain, ':');
	if ($pos) {
		$domain = substr($domain, 0, $pos);
		$port = substr($domain, $pos+1);
	} else $port = '';
	
	
}

# Function: get_ip
# Quick function to get the user's IP address.  This should probably be
# extended to account for proxies and such.
#
# Returns: 
# The numeric IP address of the client.

function get_ip() {
	return $_SERVER["REMOTE_ADDR"];
}

# Returns the string to use for a config.php file.  The levels parameter
# indicates how many levels down the directory containing this script is 
# from the blog root.  Note that this should account for situations where
# the entries or content directory is set to the empty string, although I 
# don't think anything else accounts for this yet.

function config_php_string($levels) {
	$ret  = "<?php\n";
	$ret .= 'if (! defined("PATH_SEPARATOR") ) define("PATH_SEPARATOR", strtoupper(substr(PHP_OS,0,3)==\'WIN\')?\';\':\':\');'."\n";
	if ($levels > 0) {
		$ret .= '$blogrootpath = getcwd();'."\n";
		$ret .= 'for ($i=1; $i<='.$levels.'; $i++) $blogrootpath = dirname($blogrootpath);'."\n";
	} else {
		$ret .= 'if(! defined("BLOG_ROOT")) define("BLOG_ROOT", getcwd());'."\n";
	}
	$ret .= 'if (! defined("BLOG_ROOT")) define("BLOG_ROOT", $blogrootpath);'."\n";
	$ret .= 'ini_set(\'include_path\', ini_get(\'include_path\').PATH_SEPARATOR.BLOG_ROOT);'."\n";
	$ret .= 'require_once("pathconfig.php");'."\n";
	$ret .= 'require_once("blogconfig.php");'."\n";
	$ret .= '?>';
	return $ret;
}

function pathconfig_php_string($inst_root, $inst_url, $blog_url) {
	$config_data = "<?php\n";
	$config_data .= '@define("INSTALL_ROOT", \''.$inst_root."');\n";
	$config_data .= '@define("INSTALL_ROOT_URL", \''.$inst_url."');\n";
	#$config_data .= '@define("BLOG_ROOT_URL", \''.$blog_url."');\n";
	$config_data .= 'ini_set(\'include_path\', ini_get(\'include_path\').PATH_SEPARATOR.INSTALL_ROOT);';
	$config_data .= "\n?>";
	return $config_data;
}

# Create the required wrapper scripts for a directory.  Note that
# the instpath parameter is for the software installation path 
# and is only required for the BLOG_BASE type, as it is used 
# to create the config.php file.

function create_directory_wrappers($path, $type, $instpath="") {

	$fs = NewFS();

	$head = "<?php\nrequire_once(\"config.php\");\ninclude(\"";
	$tail = ".php\");\n?>";

	$blog_templ_dir = "BLOG_ROOT.'".PATH_DELIM.BLOG_TEMPLATE_DIR."'";
	$sys_templ_dir = "INSTALL_ROOT.'".PATH_DELIM.BLOG_TEMPLATE_DIR."'";

	if (! is_dir($path)) $ret = $fs->mkdir_rec($path);

	$current = $path.PATH_DELIM;
	$parent = dirname($path).PATH_DELIM;
	$ret = 1;
	
	switch ($type) {
		case BLOG_BASE:
			if (!is_dir($instpath)) return false;
			$filelist = array("index"=>"pages/showblog", "new"=>"pages/newentry",
			                  "newart"=>"pages/newentry", "edit"=>"updateblog",
			                  "login"=>"bloglogin", "logout"=>"bloglogout",
			                  "uploadfile"=>"pages/fileupload", "map"=>"sitemap",
			                  "useredit"=>"pages/editlogin", 
			                  "plugins"=>"plugin_setup", 
			                  "tags"=>"pages/tagsearch",
			                  "pluginload"=>"plugin_loading",
			                  "profile"=>"userinfo");
			$ret &= $fs->write_file($current."config.php", config_php_string(0));
			if (! file_exists($current."pathconfig.php")) {
				$inst_root = realpath($instpath);
				$blog_root = realpath($path);
				$inst_url = localpath_to_uri($inst_root);
				$blog_url = localpath_to_uri($blog_root);
				$config_data = pathconfig_php_string($inst_root, $inst_url, $blog_url);
				$ret &= $fs->write_file($current."pathconfig.php", $config_data);
			}
			break;
		case BLOG_ENTRIES:
			$filelist = array("index"=>"pages/showarchive", "all"=>"pages/showall");
			$ret &= $fs->write_file($current."config.php", config_php_string(1));
			break;
		case YEAR_ENTRIES:
			$filelist = array("index"=>"pages/showyear");
			$ret &= $fs->write_file($current."config.php", config_php_string(2));
			break;
		case MONTH_ENTRIES:
			$filelist = array("index"=>"pages/showmonth", "day"=>"pages/showday");
			$ret &= $fs->write_file($current."config.php", config_php_string(3));
			break;
		case ENTRY_BASE:
			$filelist = array("index"=>"pages/showentry", "edit"=>"pages/editentry",
			                  "delete"=>"pages/delentry", "uploadfile"=>"pages/fileupload",
			                  "trackback"=>"pages/tb_ping");
			$ret &= $fs->write_file($current."config.php", config_php_string(4));
			break;
		case ENTRY_COMMENTS:
			$filelist = array("index"=>"pages/showcomments", "delete"=>"pages/delcomment");
			$ret &= $fs->write_file($current."config.php", config_php_string(5));
			break;
		case ENTRY_TRACKBACKS:
			$filelist = array("index"=>"pages/showtrackbacks");
			$ret &= $fs->write_file($current."config.php", config_php_string(5));
			break;
		case ARTICLE_BASE:
			# The same as for entries, but for some reason, I never added a delete.
			$filelist = array("index"=>"pages/showentry", "edit"=>"pages/editentry",
			                  "uploadfile"=>"pages/fileupload",
			                  "trackback"=>"pages/tb_ping");
			$ret &= $fs->write_file($current."config.php", config_php_string(2));
			break;
		case BLOG_ARTICLES:
			$filelist = array("index"=>"pages/showarticles");
			$ret &= $fs->write_file($current."config.php", config_php_string(1));
			break;
		case ENTRY_DRAFTS:
			$filelist = array("index"=>"pages/showdraft");
			$ret &= $fs->write_file($current."config.php", config_php_string(1));
			break;
	}
	foreach ($filelist as $file=>$content) {
		$ret &= $fs->write_file($current.$file.".php", $head.$content.$tail);
	}
	$fs->destruct();
	return $ret;
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
				$ltype = LINK_SCRIPT;
				break;
			case "css":
				$l_type = LINK_STYLESHEET;
				break;
		}
	}

	# If we didn't find a suitable file type, then bail out.
	if (! $l_type) return false;

	# Find the file using a search-path metaphor.  This isn't actually 
	# configurable, though.
	
	$blog = NewBlog();
	
	# First case: check the blog directory
	if ( defined("BLOG_ROOT") && 
	     file_exists(BLOG_ROOT.PATH_DELIM.$l_type.PATH_DELIM.$name) ) {
		
		return $blog->uri('base').$l_type."/".$name;
		
	# Second case: Try the userdata directory
	} elseif ( file_exists(USER_DATA_PATH.PATH_DELIM."themes".PATH_DELIM.THEME_NAME.
	                       PATH_DELIM.$l_type.PATH_DELIM.$name) ) {
		return INSTALL_ROOT_URL.USER_DATA."/themes/".THEME_NAME."/".$l_type."/".$name;

	# Third case: check the current theme directory
	} elseif ( file_exists(INSTALL_ROOT.PATH_DELIM."themes".PATH_DELIM.THEME_NAME.
	                       PATH_DELIM.$l_type.PATH_DELIM.$name) ) {
		return INSTALL_ROOT_URL."themes/".THEME_NAME."/".$l_type."/".$name;

	# Fourth case: try the default theme
	} elseif ( file_exists(
	             mkpath(INSTALL_ROOT,"themes","default",$l_type,$name) ) ) {
		return INSTALL_ROOT_URL."themes/default/".$l_type."/".$name;

	# Last case: nothing found, so return the empty string.
	} else {
		return $name;
	}
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

# Function: stripslashes_smart
# A smarter version of stripslashes(), based on a regular expression.
#
# Parameters:
# str - The string to strip.
#
# Retruns:
# The string with multiple backslashes converted to a single one and 
# backslashes before quotes removed.

function stripslashes_smart($str) {
	$matches = array("/\\'/", '/\\"/', '/\\\+/');
	$replacements = array("'", '"', "\\");
	return preg_replace($matches, $replacements, $str);
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
	elseif (strpos($base, '?') !== false) {
		$url_parms = substr($base, strpos($base, '?')+1);
		$url_parms = str_replace('&amp;', '&', $base);
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

if (! function_exists('is_a')) {
	function is_a($obj, $class) {
		$cls = strtolower(get_class($obj));
		$class = strtolower(trim($class));
		return ($cls == $class);
	}
}

?>
