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

function current_uri($relative=false, $query_string=false, $no_escape=false) {
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

function current_url($force_path = '') {
    $path = $force_path ?: SERVER("PHP_SELF");
    # Add the protocol and server.
    $protocol = "http";
    if (SERVER("HTTPS") == "on") $protocol = "https";
    $host = SERVER("SERVER_NAME");
    $port = SERVER("SERVER_PORT");
    if ($port == 80 || $port == "") $port = "";
    else $port = ":".$port;
    return $protocol."://".$host.$port.$path;
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

# Function: get_user_agent
# Get the user's user-agent string, or a default if it is not set.
#
# Returns:
# A string representing the user agent.
function get_user_agent() {
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        return $_SERVER['HTTP_USER_AGENT'];
    } else {
        return 'Unknown/0.0';
    }
}

# Returns the string to use for a config.php file.  The levels parameter
# indicates how many levels down the directory containing this script is
# from the blog root.  Note that this should account for situations where
# the entries or content directory is set to the empty string, although I
# don't think anything else accounts for this yet.

function config_php_string($levels) {
    $ret = '$BLOG_ROOT_DIR = ';
    $file_part = 'dirname(__FILE__)';
    if ($levels > 0) {
        for ($i = 1; $i <= $levels; $i++) {
            #$ret .= DIRECTORY_SEPARATOR.'..';
            $file_part = 'dirname(' . $file_part . ')';
        }
    }
    $ret .= "$file_part;\n";
    $ret .= 'require_once "$BLOG_ROOT_DIR/pathconfig.php";'."\n";
    return $ret;
}

function pathconfig_php_string($inst_root) {
    $config_data = "<?php\n";
    $config_data .= "# Update this path if the LnBlog directory moves\n";
    $config_data .= '$BLOG_ROOT_DIR = __DIR__;' . "\n";
    $config_data .= "require_once '$inst_root/blogconfig.php';\n";
    return $config_data;
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

    $url_map = [];
    if (defined("BLOG_ROOT")) {
        $url_map[Path::mk(BLOG_ROOT, $l_type, $name)] = 
            $blog->uri('base').$l_type."/".$name;
        $url_map[Path::mk(BLOG_ROOT, "third-party", $l_type, $name)] = 
            $blog->uri('base')."third-party/$l_type/$name";
    }
    $url_map[Path::mk(USER_DATA_PATH, "themes", THEME_NAME, $l_type, $name)] =
        INSTALL_ROOT_URL.USER_DATA."/themes/".THEME_NAME."/$l_type/$name";
    $url_map[Path::mk(INSTALL_ROOT, "themes", THEME_NAME, $l_type, $name)] =
        INSTALL_ROOT_URL."themes/".THEME_NAME."/$l_type/$name";
    $url_map[Path::mk(INSTALL_ROOT, "themes", "default", $l_type, $name)] =
        INSTALL_ROOT_URL."themes/default/$l_type/$name";
    $url_map[Path::mk(INSTALL_ROOT, "third-party", $l_type, $name)] =
        INSTALL_ROOT_URL."third-party/$l_type/$name";

    $ret = $name;
    foreach ($url_map as $path => $url) {
        if (file_exists($path)) {
            $ret = $url;
            break;
        }
    }

    $urlinfo = parse_url($ret);
    if (isset($urlinfo['host']) &&
        $l_type == LINK_SCRIPT &&
        SERVER("SERVER_NAME") != $urlinfo['host']) {
        $ret = $blog->uri('script', ['script' => $name]);
    }

    // HACK: There is a not insignificant chance that this will break something.
    // I lament ever having written this function.
    $ret = preg_replace('/^https?:/', '', $ret);

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
    $resolver = new UrlResolver();
    $local_path = $resolver->uriToLocalpath($uri);

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
        $dir = Path::mk($dir_path, $dir);
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

