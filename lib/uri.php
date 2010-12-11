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

require_once("lnblogobject.php");

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
	if (!$type) $type = URI_TYPE;
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
	if (! class_exists($creator)) $creator = $partype.$type_ext;
	
	# Neither the object nor parent class have the selected URI type, try
	# dropping back to the default URI type.
	$default_ext = "URIWrapper";
	if (! class_exists($creator)) $creator = $objtype.$default_ext;
	if (! class_exists($creator)) $creator = $partype.$default_ext;
	
	return new $creator($object);
}

###############################################################################
# Class: URI
# Represents a URI.  This auto-detects the URI settings from the environment, 
# such as the host, current path, etc., and allows the caller to override them
# to create other URIs.  URI strings should be built using this class.
###############################################################################

class URI extends LnBlogObject {
	function URI() {
		$this->protocol = 'http';
		$this->host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
		$this->port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
		$this->query_string = (! empty($_GET)) ? $_GET : array();
		$this->fragment = '';
		
		if (isset($_SERVER["REQUEST_URI"])) {
			$this->path = substr($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], '?'));
		} else {
			$this->path = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
		}
		
	}
	
	function getQueryString($sep='&') {
		$qs = '';
		if (! empty($this->query_string)) {
			$pairs = array();
			foreach ($this->query_string as $key=>$val) $pairs[] ="$key=".urlencode($val);
			$qs = "?".implode($sep, $pairs);
		}
		return $qs;
	}
	
	function getFragment() {
		if ($this->fragment) return "#".$this->fragment;
		else return '';
	}
	
	function getPort() {
		if ($this->port != 80) return ":".$this->port;
		else return '';
	}
	
	# Method: get
	# Gets a string containingthe URI in the canonical format.
	function get() {
		return $this->protocol."://".
		       $this->host.
		       $this->getPort().
		       $this->path.
		       $this->getQueryString().
		       $this->getFragment();
	}
	
	# Method: getRelative
	# Gets a root-relative URI, i.e. no host, port, or protocol.
	function getRelative() {
		return $this->path.
		       $this->getQueryString().
		       $this->getFragment();
	}
	
	# Method: getPrintable
	# Like <get>, but returns a printable URI, i.e. with ampersands escaped.
	function getPrintable() {
		return $this->protocol."://".
			$this->host.
			$this->getPort().
			$this->path.
			$this->getQueryString('&amp;').
			$this->getFragment();
	}
	
	# Method: getRelativePrintable
	# Like <getPrintable>, but uses a relative URI.
	function getRelativePrintable() {
		return $this->path.
			$this->getQueryString('&amp;').
			$this->getFragment();
	}
	# Method: appendPath
	# Appends a path componend to the URI path, adding or removing terminal/beginning
	# slashes as appropriate.
	#
	# Parameters:
	# path - The path to append.
	function appendPath($path) {
		if ( substr($path, 0, 1) == "/") $path = substr($path, 1);
		if ( substr($this->path, -1) != "/") $this->path .= "/";
		$this->path .= $path;
	}
	
	# Method: clearQueryString
	# Empties the query string;
	function clearQueryString() {
		$this->query_string = array();
	}
	
	# Method: addQuery
	# Adds a parameter to the URI query string.
	#
	# Parameters:
	# key - The parameter name.
	# val - The value for that parameter.
	function addQuery($key, $val) {
		$this->query_string[$key] = $val;
	}
	
	# Method: secure
	# Set or get whether to use a secure, i.e. https, URL.
	function secure($val='') {
		if ($val === '') return $this->protocol == 'https';
		else {
			if ($this->protocol == 'https') $this->protocol = 'http';
			else $this->protocol = 'https';
			return $this->protocol;
		}
	}
}

class BlogURIWrapper extends URI {
	
	function BlogURIWrapper(&$blog) {
		$this->URI();
	
		$this->object = $blog;
		
		$this->base_uri = localpath_to_uri($blog->home_path);
		$url_bits = parse_url($this->base_uri);
		$url_components = array('protocol'=>'scheme',
		                        'host'=>'host', 'port'=>'port',
		                        'path'=>'path', 'username'=>'user',
		                        'password'=>'pass', 'path'=>'path',
		                        'query_string'=>'query',
		                        'fragment'=>'fragment');
		foreach ($url_components as $key=>$val) {
			if (isset($url_bits[$val])) {
				if ($key == 'query_string') {
					$this->{$key} = explode($url_bits[$val], '&');
				} else {
					$this->{$key} = $url_bits[$val];
				}
			}
		}
		
		$this->separator = "&amp;";
	}
	
	function setEscape($val) { $this->separator = $val ? "&amp;" : "&"; }
	
	function permalink() { return $this->base_uri; }
	function base() { return $this->base_uri; }
	function blog() { return $this->base_uri; }
	function page() { return $this->base_uri; }
	
	function articles() { return $this->base_uri.BLOG_ARTICLE_PATH."/"; }
	function entries() { return $this->base_uri.BLOG_ENTRY_PATH."/"; }
	function archives() { return $this->entries(); }
	function drafts() { return $this->base_uri.BLOG_DRAFT_PATH."/"; }
	function listdrafts() { return $this->drafts(); }
	
	function year($year) { 	
		return $this->base_uri.BLOG_ENTRY_PATH.sprintf("/%04d/", $year);
	}
	function listyear($year) { 	return $this->year($year); }
	
	function month($year, $month) { 
		return $this->base_uri.BLOG_ENTRY_PATH.sprintf("/%04d/%02d/", $year, $month);
	}
	function listmonth($year, $month) { return $this->month($year, $month); }
	
	function day($year, $month, $day) {
		return $this->base_uri.BLOG_ENTRY_PATH.
		       sprintf("/%04d/%02d/?day=%02d", $year, $month, $day);
	}
	function showday($year, $month, $day) { return $this->day($year, $month, $day); }
	
	function listall() { return $this->base_uri.BLOG_ENTRY_PATH."/?list=yes"; }
	
	function wrapper($action, $qs_arr=false) {
		$qs = '';
		if (is_array($qs_arr)) {
			foreach ($qs_arr as $key=>$val) {
				$qs .= $this->separator."$key=$val";
			}
		} elseif ($qs_arr) {
			$qs = $this->separator.$qs_arr;
		}
		return $this->base_uri."?action=$action".$qs;
	}
	
	function addentry() { return $this->wrapper("newentry"); }
	function addarticle() {
		return $this->wrapper("newentry", array("type"=>"article"));
	}
	function delentry($entryid) {
		return $this->wrapper("delentry", array('entry'=>$entryid));
	}
	function upload() { return $this->wrapper("upload"); }
	function edit() { return $this->wrapper("edit"); }
	function manage_reply() { return $this->wrapper("managereply"); }
	function manage_all() { return $this->wrapper("managereply"); }
	function manage_year($year) {
		return $this->wrapper("managereply", array('year'=>$year));
	}
	function manage_month($year, $month) {
		return $this->wrapper("managereply", array('year'=>$year, 'month'=>$month));
	}
	function login() {
		if (defined("LOGIN_HTTPS") && LOGIN_HTTPS) 
			$this->base_uri = localpath_to_uri($this->object->home_path, true, true);
		return $this->wrapper("login");
	}
	function logout() { return $this->wrapper("logout"); }
	function editfile($args) { return $this->wrapper("editfile", $args); }
	function edituser() { return $this->wrapper("useredit"); }
	function pluginconfig() { return $this->wrapper("plugins"); }
	function pluginload() { return $this->wrapper("pluginload"); }
	function tags($tag=false) { 
		if ($tag) {
			if (strpos($tag, "=")) {
				$data = explode("=", $tag);
				$tag = array($data[0]=>$data[1]);
			} else {
				$tag = array("tag"=>$tag);
			}
			return $this->wrapper("tags", $tag);
		} else {
			return $this->wrapper("tags"); 
		}
	}
	
	# These functions provide wrappers for cross-domain scripting.
	function script($script) { return $this->base_uri."?script=$script"; }
	function plugin($plug, $qs_arr=false) {
		$qs = "plugin=$plug";
		if (is_array($qs_arr)) {
			foreach ($qs_arr as $key=>$val) {
				$qs .= $this->separator."$key=$val";
			}
		}
		return $this->base_uri."?$qs"; 
	}

}

class BlogURIhtaccess extends BlogURIWrapper {

}

class BlogURIQueryString extends BlogURIWrapper {
	
}

class BlogURIHybrid extends BlogURIWrapper {
	
	function BlogURIHybrid(&$blog) {
		$this->object = $blog;
		$this->base_uri = localpath_to_uri($blog->home_path);
		$this->install_uri = INSTALL_ROOT_URL;
		$this->separator = "&amp;";
	}
	
	function addentry() { 
		$u = new URI();
		
		return make_uri($this->install_uri."pages/entryedit.php", 
	                    array("blog"=>$this->object->blogid));
	}
	function addarticle() {
		return make_uri($this->install_uri."pages/entryedit.php", 
	                    array("blog"=>$this->object->blogid,
		                      "type"=>"article"));
	}

}

class BlogEntryURIWrapper extends LnBlogObject {
	function BlogEntryURIWrapper(&$ent) {
		$this->object = $ent;
		$this->base_uri = localpath_to_uri(dirname($ent->file));
		$this->separator = "&amp;";
	}
	
	function setEscape($val) { $this->separator = $val ? "&amp;" : "&"; }
	
	function permalink() {
		$ent =& $this->object;
		$pretty_file = $ent->calcPrettyPermalink();
		if ($pretty_file)
			$pretty_file = mkpath(dirname($ent->localpath()),$pretty_file);
		if ( file_exists($pretty_file) ) {
			# Check for duplicated entry subjects.
			$base_path = substr($pretty_file, 0, strlen($pretty_file)-5);
			$i = 2;
			if ( file_exists( $base_path.$i.".php") ) {
				while ( file_exists( $base_path.$i.".php" ) ) {
					$contents = file_get_contents($base_path.$i.".php");
					if (strpos($contents, basename(dirname($ent->file)))) {
						return localpath_to_uri($base_path.$i.".php");
					}
					$i++;
				}
				return $this->base_uri;
			} else {
				return localpath_to_uri($pretty_file);
			}
		} else {
			$pretty_file = $ent->calcPrettyPermalink(true);
			if ($pretty_file)
				$pretty_file = mkpath(dirname($ent->localpath()),$pretty_file);
			if ( file_exists($pretty_file) ) {
				return localpath_to_uri($pretty_file);
			} else {
				return $this->base_uri;;
			}
		}
	}
	
	function entry() { return $this->permalink(); }
	function page() { return $this->permalink(); } 
	
	function base() { return $this->base_uri; }
	function basepage() { return $this->base_uri."index.php"; } 
	
	function comment() { return $this->base_uri.ENTRY_COMMENT_DIR."/"; }
	function commentpage() { return $this->base_uri.ENTRY_COMMENT_DIR."/index.php"; }
	
	function send_tb() { return $this->base_uri.ENTRY_TRACKBACK_DIR."/?action=ping"; }
	function get_tb() { return $this->base_uri.ENTRY_TRACKBACK_DIR."/index.php"; }
	function trackback() { return $this->base_uri.ENTRY_TRACKBACK_DIR."/"; }
	function pingback() { return $this->base_uri.ENTRY_PINGBACK_DIR."/"; }
	function upload() {return $this->base_uri."/?action=upload"; }
	
	function edit() { return $this->base_uri."?action=edit"; }
	function editDraft($preview=false) {
		$b = NewBlog();
		$params = array('action'=>'edit', 'draft'=>$this->object->entryID());
		if ($preview) $params['preview'] = 'yes';
		return make_uri($b->uri('drafts'), $params, true, $this->separator);
	}
	
	function delete() {
		$ent =& $this->object;
		if ($ent->isDraft()) {
			$entry_type = 'draft';
		} else {
			$entry_type = is_a($ent, 'Article') ? 'article' : 'entry';
		}
		$qs_arr['blog'] = $ent->parentID();
		$qs_arr[$entry_type] = $ent->entryID();
		return make_uri(INSTALL_ROOT_URL."pages/delentry.php", $qs_arr);
	}
	
	function manage_reply() {
		return $this->base_uri."?action=managereplies";
	}
	function managereply() { return $this->manage_reply(); }
}

class BlogEntryURIHybrid extends BlogEntryURIWrapper {
	
	function BlogEntryURIHybrid(&$ent) {
		$this->object = $ent;
		$this->base_uri = localpath_to_uri(dirname($ent->file));
		$this->install_uri = INSTALL_ROOT_URL;
		$this->separator = "&amp;";
	}
	
	function edit() { 
		$b = NewBlog();
		return make_uri($b->uri("addentry"), 
		                array('entry'=>$this->object->entryID()));
	}
	
	function editDraft() {
		$b = NewBlog();
		return make_uri($b->uri("addentry"), 
		                array('draft'=>$this->object->entryID()));
	}
}

class BlogCommentURIWrapper extends LnBlogObject {
	function BlogCommentURIWrapper(&$ent) {
		$this->object = $ent;
		$this->base_uri = localpath_to_uri(dirname($ent->file));
		$this->separator = "&amp;";
	}
	
	function setEscape($val) { $this->separator = $val ? "&amp;" : "&"; }
	
	function permalink() { return $this->base_uri."#".$this->object->getAnchor(); }
	function comment() { return $this->permalink(); }
	function delete() {
		$qs_arr = array();
		$parent = $this->object->getParent();
		$entry_type = is_a($this->object, 'Article') ? 'article' : 'entry';
		$qs_arr['blog'] = $parent->parentID();
		$qs_arr[$entry_type] = $parent->entryID();
		$qs_arr['delete'] = $this->object->getAnchor();
		return make_uri(INSTALL_ROOT_URL."pages/delcomment.php", $qs_arr);
	}
}

class TrackbackURIWrapper extends LnBlogObject {
	function TrackbackURIWrapper(&$ent) {
		$this->object = $ent;
		$this->base_uri = localpath_to_uri(dirname($ent->file));
		$this->separator = "&amp;";
	}
	
	function setEscape($val) { $this->separator = $val ? "&amp;" : "&"; }

	function trackback() { return $this->base_uri."#".$this->object->getAnchor(); }
	function pingback() { return $this->base_uri."#".$this->object->getAnchor(); }
	
	function permalink() { return $this->trackback(); }
	function delete() {
		$qs_arr = array();
		$parent = $this->object->getParent();
		$entry_type = is_a($this->object, 'Article') ? 'article' : 'entry';
		$qs_arr['blog'] = $parent->parentID();
		$qs_arr[$entry_type] = $parent->entryID();
		$qs_arr['delete'] = $this->object->getAnchor();
		return make_uri(INSTALL_ROOT_URL."pages/delcomment.php", $qs_arr);
	}
	
}
?>
