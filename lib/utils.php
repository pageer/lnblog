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

define("LOGIN_TOKEN", "lToken");
define("LAST_LOGIN_TIME", "lastLTime");
define("CURRENT_USER", "uName");

# Types of directories to create.
define("BLOG_BASE", 0);
define("BLOG_ENTRIES", 1);
define("YEAR_ENTRIES", 2);
define("MONTH_ENTRIES", 3);
define("ENTRY_BASE", 4);
define("ENTRY_COMMENTS", 5);
define("ARTICLE_BASE", 6);
define("BLOG_ARTICLES", 7);

# Write contents to a file.  Basically a wrapper around fopen and fwrite.

function write_file($path, $contents, $mode="w") {
	$fh = fopen($path, $mode);
	if ($fh) {
		$ret = fwrite($fh, $contents);
		fclose($fh);
	} else $ret = false;
	return $ret;
}

# Makes directory recursively.

function mkdir_rec($path, $mode=0777) {
	$parent = dirname($path);
	if ( $parent == $path ) return false;
	$old_mask = umask(0000);
	if (! is_dir($parent) )	$ret = mkdir_rec($parent, $mode);
	else $ret = true;
	if ($ret) $ret = mkdir($path, $mode);
	umask($old_mask);
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
# NOTE: Use of realpath() requires PHP 4.

function find_document_root($path=false) {
	# List of common document root names.
	$doc_roots = array("wwwroot", "inetpub", "htdocs", "htsdocs", "httpdocs", 
	                   "httpsdocs", "webroot");
	$curr_path = $path ? realpath($path) : getcwd();
	$parent_dir = dirname($curr_path);
	$base_dir = basename($curr_path);
	# If we've hit the beginning of the hierarchy, return failure.
	if ($parent_dir == $curr_path) return false;
	foreach ($doc_roots as $root)
		if ( strtolower($base_dir) == strtolower($root) ) return $curr_path;
	return find_document_root($parent_dir);
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
		if ($val) $ret = $_POST[$key] = $val;
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

# Convenience function to return the path to the current script.  
# Useful for the action attribute in form tags.

function current_uri ($relative=false) {
	$ret = SERVER("PHP_SELF"); 
	if ($relative) $ret = basename($ret);
	return $ret;
}

# Returns the file name, without path, of the current script.

function current_file() {
	return current_uri(true);
}

# Convert a local path to a URI.
# NOTE: Use of realpath() requires PHP 4.

function localpath_to_uri($path, $full_uri=true) {
	$full_path = realpath($path);
	# Add a trailing slash if the path is a directory.
	if (is_dir($full_path)) $full_path .= PATH_DELIM;
	$root = find_document_root($full_path);
	$url_path = str_replace($root, "", $full_path);
	# Remove any drive letter.
	if ( strtoupper( substr(PHP_OS,0,3) ) == 'WIN' ) 
		$url_path = preg_replace("/[A-Z]:(.*)/", "$1", $url_path);
	# Convert to forward slashes.
	if (PATH_DELIM != "/") 
		$url_path = str_replace(PATH_DELIM, "/", $url_path);
	
	if ($full_uri) {
		# Add the protocol and server.
		$protocol = "http";
		$host = SERVER("SERVER_NAME");
		$url_path = $protocol."://".$host.$url_path;
	}
	return $url_path;
}

# Quick function to get the user's IP address.

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

# A copy function that reads data from src and writes it to dest.

function fcopy($src, $dest) {
	$fh = fopen($src, "r");
	$length = filesize($src);
	$contents = fread($fh, $length);
	fclose($fh);
	$fh = fopen($dest, "w");
	$ret = fwrite($fh, $contents, $length);
	fclose($fh);
	return $ret;
}

# Create the required wrapper scripts for a directory.  Note that
# the instpath parameter is for the software installation path 
# and is only required for the BLOG_BASE type, as it is used 
# to create the config.php file.
# NOTE: Uses realpath(), to requires PHP 4.

function create_directory_wrappers($path, $type, $instpath="") {

	$head = "<?php\nrequire_once(\"config.php\");\ninclude(\"";
	$tail = ".php\");\n?>";

	$blog_templ_dir = "BLOG_ROOT.'".PATH_DELIM.BLOG_TEMPLATE_DIR."'";
	$sys_templ_dir = "INSTALL_ROOT.'".PATH_DELIM.BLOG_TEMPLATE_DIR."'";

	if (! is_dir($path)) $ret = mkdir_rec($path);
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
	
	$config_data .= 'ini_set(\'include_path\', ini_get(\'include_path\').PATH_SEPARATOR.BLOG_ROOT.PATH_SEPARATOR.INSTALL_ROOT);';
	#.PATH_SEPARATOR.'.$blog_templ_dir.'.PATH_SEPARATOR.'.$sys_templ_dir.");\n";
	$config_data .= "\n".'require_once("blogconfig.php");';
	$config_data .= "\n?>";

	$current = $path.PATH_DELIM;
	$parent = dirname($path).PATH_DELIM;
	$ret = 1;
	
	switch ($type) {
		case BLOG_BASE:
			if (!is_dir($instpath)) return false;
			$ret &= write_file($current."index.php", $head."showblog".$tail);
			$ret &= write_file($current."new.php", $head."newentry".$tail);
			$ret &= write_file($current."newart.php", $head."newarticle".$tail);
			$ret &= write_file($current."edit.php", $head."updateblog".$tail);
			$ret &= write_file($current."login.php", $head."bloglogin".$tail);
			$ret &= write_file($current."logout.php", $head."bloglogout".$tail);
			$ret &= write_file($current."config.php", $config_data);
			break;
		case BLOG_ENTRIES:
			$ret = write_file($current."index.php", $head."showarchive".$tail);
			$ret &= copy($parent."config.php", $current."config.php");
			break;
		case YEAR_ENTRIES:
			$ret = write_file($current."index.php", $head."showyear".$tail);
			$ret &= copy($parent."config.php", $current."config.php");
			break;
		case MONTH_ENTRIES:
			$ret = write_file($current."index.php", $head."showmonth".$tail);
			$ret &= copy($parent."config.php", $current."config.php");
			break;
		case ENTRY_BASE:
			$ret = write_file($current."index.php", $head."showentry".$tail);
			$ret &= write_file($current."edit.php", $head."editentry".$tail);
			$ret &= copy($parent."config.php", $current."config.php");
			break;
		case ENTRY_COMMENTS:
			$ret = write_file($current."index.php", $head."showcomments".$tail);
			$ret = write_file($current."delete.php", $head."delcomment".$tail);
			$ret &= copy($parent."config.php", $current."config.php");
			break;
		case ARTICLE_BASE:
			$ret = write_file($current."index.php", $head."showarticle".$tail);
			$ret &= write_file($current."edit.php", $head."editarticle".$tail);
			$ret &= copy($parent."config.php", $current."config.php");
			break;
		case BLOG_ARTICLES:
			$ret = write_file($current."index.php", $head."showarticles".$tail);
			$ret &= copy($parent."config.php", $current."config.php");
			break;
	}

	return $ret;
}

# User authentication functions. 

# Authentication can be on a system wide or per-directory basis.  The username and 
# password are stored in the passwd.php file as defined consntants and the file is
# require()ed to retreive the values.  Note that the password is an MD5 hash or the 
# actual password contactenated with the username.

# Authentication is checked by comparing a server-side session variable 
# to a client side cookie.

function make_login_token($ts) {
	return md5(get_ip().$ts);
}

function create_passwd_file($dirpath, $uname, $pwd) {
	$uid = trim($uname);
	$pass = trim($pwd);
	$content =  "<?php\n";
	$content .= "define(\"AUTH_USERNAME\", \"".$uid."\");\n";
	$content .= "define(\"AUTH_PASSWORD\", \"".md5($pass.$uid)."\");\n";
	$content .= "?>";
	return write_file($dirpath.PATH_DELIM."passwd.php", $content);
}

function do_login($uname, $pwd) {
	if ( trim($uname) == "" || trim($pwd) == "" ) return false;
	require("passwd.php");
	$uid = trim($uname);
	$pass = trim($pwd);
	$user_name = AUTH_USERNAME;
	$password = AUTH_PASSWORD;
	$check_passwd = md5($pass.$uid);
	if ($uname == $user_name && $password == $check_passwd) {
		# Create a login token.
		$ts = time();
		$token = make_login_token($ts);
		#setcookie(LOGIN_TOKEN, $token);
		setcookie(LAST_LOGIN_TIME, $ts);
		SESSION(CURRENT_USER, AUTH_USERNAME);
		SESSION(LOGIN_TOKEN, $token);
		SESSION(LAST_LOGIN_TIME, $ts);
		$ret = true;
	} else $ret = false;
	return $ret;
}

function do_logout() {
	SESSION(CURRENT_USER, false);
	SESSION(LOGIN_TOKEN, false);
	SESSION(LAST_LOGIN_TIME, false);
	setcookie(LOGIN_TOKEN, "");
	setcookie(LAST_LOGIN_TIME, "");
}

function check_login()  {
	$cookie_ts = COOKIE(LAST_LOGIN_TIME);
	$auth_token = make_login_token($cookie_ts);
	$auth_ok = ($auth_token == SESSION(LOGIN_TOKEN) );
	$auth_ok &= ($cookie_ts == SESSION(LAST_LOGIN_TIME) );
	#echo "<p>$cookie_ts</p><p>$auth_token</p>";
	if ($auth_ok) return true;
	else return false;
}

?>
