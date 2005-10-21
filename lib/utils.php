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

# Types of files for use with the getlink() function.
# For convenience, these are defined as the directories under a theme.
define("LINK_IMAGE", "images");
define("LINK_STYLESHEET", "styles");
define("LINK_SCRIPT", "scripts");

# Return the canonical path for a given file.  The file need no exist.
# Removes all '.' and '..' components and returns an absolute path.

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

# Return whether or not a path is absolute.

function is_absolute($path) {
	if (PATH_DELIM == "/") {
		return ( substr($path, 0, 1) == "/" );
	} else {
		return ( substr($path, 1, 2) == ":\\" );
	}
}

# Write contents to a file.  Basically a wrapper around fopen and fwrite.
# This function was rewritten to use the FS abstract interface.  For 
# one-off writes, this is fine, but for writing multiple files in the
# same call, the FS interface should be used directly.

function write_file($path, $contents) {
	$fs = NewFS();
	$ret = $fs->write_file($path, $contents);
	$fs->destruct();
	return $ret;
}

# Makes directory recursively.
# This was also rewritten to use the FS interface.  Same deal.

function mkdir_rec($path, $mode=false) {
	$fs = NewFS();
	# Only pass mode if it is specified.  This lets us default to the
	# appropriate default mode for the concrete subclass of FS, which is
	# different between, say, FTPFS and NativeFS.
	if ($mode) $ret = $fs->mkdir_rec($path, $mode);
	else $ret = $fs->mkdir_rec($path);
	return $ret;
}

# Does essentially the same thing as scandir on PHP 5.  Returns an array of
# directory entry names, removing "." and ".." entries.

function scan_directory($path, $dirs_only=false) {
	if (! is_dir($path)) return false;
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

# Finds the document root by checking common directory names.
# This assumes that we are, in fact, currently somewhere under it.

function find_document_root($path=false) {
	# Check if we have a DOCUMENT_ROOT defined.  This is set at setup time
	# and stored in the fsconfig.php file.
	if ( defined("DOCUMENT_ROOT") ) return DOCUMENT_ROOT;
	# Get list of common document root names.
	$doc_roots = explode(",", DOCROOT_NAMES);
	$curr_path = $path ? realpath($path) : getcwd();
	$parent_dir = dirname($curr_path);
	$base_dir = basename($curr_path);
	# If we've hit the beginning of the hierarchy, return failure.
	if ($parent_dir == $curr_path) return false;
	foreach ($doc_roots as $root)
		if ( strcasecmp($base_dir, $root) == 0 ) return $curr_path;
	return find_document_root($parent_dir);
}

# An alternate way to find the document root.  This one works by comparing
# the current URL on the server to the current directory.  The idea is that
# we can find the location of the current URL in the path and remove it to
# get the document root.
# NOTE: This function IS case-sensitive.  It also assumes that the 

function calculate_document_root() {
	# Bail out if DOCUMENT_ROOT is already defined.
	if ( defined("DOCUMENT_ROOT") ) return DOCUMENT_ROOT;
	
	# Get the current URL and the path to the file.
	$curr_uri = current_uri();
	$curr_file = getcwd().PATH_DELIM.basename($curr_uri);
	if (PATH_DELIM != "/") $curr_uri = str_replace("/", PATH_DELIM, $curr_uri);

	if ( preg_match(URI_TO_LOCALPATH_MATCH_RE, $curr_uri) ) {
		$curr_uri = preg_replace(URI_TO_LOCALPATH_MATCH_RE, URI_TO_LOCALPATH_REPLACE_RE,$curr_uri);
	}

	# Find the location 
	$pos = strpos($curr_file, $curr_uri);
	return substr($curr_file, 0, $pos + 1);
	
}

# Version comparison function, since we want this to work with PHP 4.0 as
# well as version 4.1, which is when version_compare() was introduced.
# Compares 1.2.3 style version numbers and returns 1 if ver1 is greater, 
# -1 if ver1 is less, and 0 if they're equal.

function compare_standard_version($ver1, $ver2) {
	$ver1_arr = explode(".", $ver1);
	$ver2_arr = explode(".", $ver2);
	for ($i=0; $i < 3; $i++)  
		if ($ver1_arr[$i] != $ver2_arr[$i]) 
			return $ver1_arr[$i] > $ver2_arr[$i] ? 1 : -1;
	return 0;
}

# Check that PHP is at least a certain version.

function php_version_at_least($ver) {
	$res = compare_standard_version(phpversion(), $ver);
	if ($res < 0) return false;
	else return true;
}

# These function account for use of $HTTP_SERVER_VARS, $HTTP_SESSION_VARS, 
# and so forth in PHP versions prior to 4.1.0.  They also provide a handy 
# way to avoid undefined variable warnings without explicitly calling
# isset() or empty() every time.

function SESSION($key, $val="") {
	if (php_version_at_least("4.1.0")) {
		if ($val) return $_SESSION[$key] = $val;
		elseif (isset($_SESSION[$key])) return $_SESSION[$key];
		else return false;
	} else {
		if ($val) return $HTTP_SESSION_VARS[$key] = $val;
		elseif (isset($HTTP_SESSION_VARS[$key])) return $HTTP_SESSION_VARS[$key];
		else return false;
	}
}

function SERVER($key, $val="") {
	if (php_version_at_least("4.1.0")) {
		if ($val) return $_SERVER[$key] = $val;
		elseif (isset($_SERVER[$key])) return $_SERVER[$key];
		else return false;
	} else {
		if ($val) return $HTTP_SERVER_VARS[$key] = $val;
		elseif (isset($HTTP_SERVER_VARS[$key])) return $HTTP_SERVER_VARS[$key];
		else return false;
	}
}

function COOKIE($key, $val="") {
	if (php_version_at_least("4.1.0")) {
		if ($val) return $_COOKIE[$key] = $val;
		elseif (isset($_COOKIE[$key])) return $_COOKIE[$key];
		else return false;
	} else {
		if ($val) return $HTTP_COOKIE_VARS[$key] = $val;
		elseif (isset($HTTP_COOKIE_VARS[$key])) return $HTTP_COOKIE_VARS[$key];
		else return false;
	}
}

function POST($key, $val="") {
	if (php_version_at_least("4.1.0")) {
		if ($val) return $_POST[$key] = $val;
		elseif (isset($_POST[$key])) return $_POST[$key];
		else return false;
	} else {
		if ($val) return $HTTP_POST_VARS[$key] = $val;
		elseif (isset($HTTP_POST_VARS[$key])) return $HTTP_POST_VARS[$key];
		else return false;
	}
}

function GET($key, $val="") {
	if (php_version_at_least("4.1.0")) {
		if ($val) return $_GET[$key] = $val;
		elseif (isset($_GET[$key])) return $_GET[$key];
		else return false;
	} else {
		if ($val) return $HTTP_GET_VARS[$key] = $val;
		elseif (isset($HTTP_GET_VARS[$key])) return $HTTP_GET_VARS[$key];
		else return false;
	}
}

function has_post() {
	if (php_version_at_least("4.1.0")) {
		return count($_POST);
	} else {
		return count($HTTP_POST_VARS);
	}
}

function has_get() {
	if (php_version_at_least("4.1.0")) {
		return count($_GET);
	} else {
		return count($HTTP_GET_VARS);
	}
}

# Convenience function to return the path to the current script.  
# Useful for the action attribute in form tags.

function current_uri ($relative=false) {
	$ret = SERVER("SCRIPT_NAME"); 
	if ($relative) $ret = basename($ret);
	return $ret;
}

# Returns the file name, without path, of the current script.

function current_file() {
	return current_uri(true);
}

# Returns the absolute URL of the requested script.

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

# Convert a local path to a URI.

function localpath_to_uri($path, $full_uri=true) {
	$full_path = realpath($path);
	# Add a trailing slash if the path is a directory.
	if (is_dir($full_path)) $full_path .= PATH_DELIM;
	#$root = find_document_root($full_path);
	$root = calculate_document_root();
	#$url_path = str_replace($root, "", $full_path);
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
		$url_path = preg_replace("/[A-Z]:(.*)/", "$1", $url_path);
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

# A quick function to swap back to the default style/image/script if the
# one requested doesn't exist in the current theme.
function link_item($item) {
	
	if (is_file($item)) return $item;
	# Determine what type of item we have by file extension.  This allows the
	# user to create single-directory themes.
	$pos = strrpos($item, ".");
	if (!$pos) return $item;
	$ext = strtolower(substr($item, $pos));
	$file = basename($item);
	switch ($ext) {
		case ".css": 
			$ret = DEFAULT_STYLES."/".$file;
			break;
		case ".js":
		case ".vbs":
			$ret = DEFAULT_SCRIPTS."/".$file;
			break;
		case ".jpg":
		case ".jpeg":
		case ".gif":
		case ".png":
		case ".tif":
		case ".tiff":
			$ret = DEFAULT_IMAEGS."/".$file;
			break;
	}
	return $ret;
}

# Quick function to get the user's IP address.  This should probably be
# extended to account for proxies and such.

function get_ip() {
	return $_SERVER["REMOTE_ADDR"];
}

# Wrapper functions for HTTP redirection and refreshing.

function redirect($path) {
	header("Location: ".$path);
}

function refresh($path, $delay=0) {
	header("Refresh: ".$delay."; ".$path);
}

# Create the required wrapper scripts for a directory.  Note that
# the instpath parameter is for the software installation path 
# and is only required for the BLOG_BASE type, as it is used 
# to create the config.php file.
# NOTE: Uses realpath(), to requires PHP 4.

function create_directory_wrappers($path, $type, $instpath="") {

	$fs = NewFS();

	$head = "<?php\nrequire_once(\"config.php\");\ninclude(\"";
	$tail = ".php\");\n?>";

	$blog_templ_dir = "BLOG_ROOT.'".PATH_DELIM.BLOG_TEMPLATE_DIR."'";
	$sys_templ_dir = "INSTALL_ROOT.'".PATH_DELIM.BLOG_TEMPLATE_DIR."'";

	if (! is_dir($path)) $ret = $fs->mkdir_rec($path);
	$config_data = "<?php\n";
	$config_data .= "if (! defined(\"PATH_SEPARATOR\") ) ".
		"define(\"PATH_SEPARATOR\", strtoupper(substr(PHP_OS,0,3)=='WIN')?';':':');\n";
		
	$inst_root = realpath($instpath);
	$blog_root = realpath($path);
	$inst_url = localpath_to_uri($inst_root);
	$blog_url = localpath_to_uri($blog_root);
	
	$config_data .= 'define("INSTALL_ROOT", \''.$inst_root."');\n";
	$config_data .= 'define("INSTALL_ROOT_URL", \''.$inst_url."');\n";
	$config_data .= 'define("BLOG_ROOT", \''.$blog_root."');\n";
	$config_data .= 'define("BLOG_ROOT_URL", \''.$blog_url."');\n";
	
	$config_data .= 'ini_set(\'include_path\', ini_get(\'include_path\').PATH_SEPARATOR.'.
	                                           'BLOG_ROOT.PATH_SEPARATOR.INSTALL_ROOT);';
	$config_data .= "\n".'require_once("blogconfig.php");';
	$config_data .= "\n?>";

	$current = $path.PATH_DELIM;
	$parent = dirname($path).PATH_DELIM;
	$ret = 1;
	
	switch ($type) {
		case BLOG_BASE:
			if (!is_dir($instpath)) return false;
			$filelist = array("index"=>"pages/showblog", "new"=>"pages/newentry", 
			                  "newart"=>"pages/newarticle", "edit"=>"updateblog",
			                  "login"=>"bloglogin", "logout"=>"bloglogout",
			                  "uploadfile"=>"pages/fileupload", "map"=>"sitemap",
			                  "useredit"=>"pages/editlogin");
			$ret &= $fs->write_file($current."config.php", $config_data);
			break;
		case BLOG_ENTRIES:
			$filelist = array("index"=>"pages/showarchive", "all"=>"pages/showall");
			break;
		case YEAR_ENTRIES:
			$filelist = array("index"=>"pages/showyear");
			break;
		case MONTH_ENTRIES:
			$filelist = array("index"=>"pages/showmonth", "day"=>"pages/showday");
			break;
		case ENTRY_BASE:
			$filelist = array("index"=>"pages/showentry", "edit"=>"pages/editentry",
			                  "delete"=>"pages/delentry", "uploadfile"=>"pages/fileupload",
			                  "trackback"=>"pages/tb_ping");
			break;
		case ENTRY_COMMENTS:
			$filelist = array("index"=>"pages/showcomments", "delete"=>"pages/delcomment");
			break;
		case ENTRY_TRACKBACKS:
			$filelist = array("index"=>"pages/showtrackbacks");
			break;
		case ARTICLE_BASE:
			# The same as for entries, but for some reason, I never added a delete.
			$filelist = array("index"=>"pages/showarticle", "edit"=>"pages/editarticle",
			                  "uploadfile"=>"pages/fileupload",
			                  "trackback"=>"pages/tb_ping");
			break;
		case BLOG_ARTICLES:
			$filelist = array("index"=>"pages/showarticles");
			break;
	}
	foreach ($filelist as $file=>$content) {
		$ret &= $fs->write_file($current.$file.".php", $head.$content.$tail);
	}
	if ($type != BLOG_BASE) {
		$ret &= $fs->copy($parent."config.php", $current."config.php");
	}
	$fs->destruct();
	return $ret;
}

# Returns a path suitable for use with <link> tags or as an href or src 
# attribute.  Takes a file name and optional type (script, image, or style 
# sheet) and returns a URI.  If the type is not given, the function guesses 
# it from the file extension.

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
				$l_type = LINE_STYLESHEET;
				break;
		}
	}

	# If we didn't find a suitable file type, then bail out.
	if (! $l_type) return false;

	# Find the file using a search-path metaphor.  This isn't actually 
	# configurable, though.
	
	# First case: check the blog directory
	if ( defined("BLOG_ROOT") && defined("BLOG_ROOT_URL") &&
	     file_exists(BLOG_ROOT.PATH_DELIM.$l_type.PATH_DELIM.$name) ) {
		
		return BLOG_ROOT_URL.$l_type."/".$name;
		
	# Second case: check the current theme directory
	} elseif ( file_exists(INSTALL_ROOT.PATH_DELIM."themes".PATH_DELIM.THEME_NAME.
	                       PATH_DELIM.$l_type.PATH_DELIM.$name) ) {
	
		return INSTALL_ROOT_URL."themes/".THEME_NAME."/".$l_type."/".$name;

	# Last case: use the default theme
	} else {
		return INSTALL_ROOT_URL."themes/default/".$l_type."/".$name;
	}

}

function sanitize($str, $pattern="/\W/", $sub="") {
	if (preg_match($pattern, $str)) {
		return preg_replace($pattern, $sub, $str);
	} else {
		return $str;
	}
}

function stripslashes_smart($str) {
	$matches = array("/\\'/", '/\\"/', '/\\\+/');
	$replacements = array("'", '"', "\\");
	return preg_replace($matches, $replacements, $str);
}

?>
