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

/*
class AbstractURIFactory extends LnBlogObject {
	
	function AbstractURIFactory() {}
	
	function getURI(&$object, &$type) {
		$type = strtolower(get_class($object));
		
		switch ($type) {
			case "blog":
				$conc_obj = new BlogURIFactory($object);
				break;
			case "blogentry":
				$conc_obj = new BlogEntryURIFactory($object);
				break;
			case "article":
				$conc_obj = new ArticleURIFactory($object);
				break;
			case "blogcomment":
				$conc_obj = new BlogCommentURIFactory($object);
				break;
			case "trackback":
				$conc_obj = new TrackbackURIFactory($object);
				break;
			case "pingback":
				$conc_obj = new PingbackURIFactory($object);
				break;
		}
		
		return $conc_obj;
	}
}
*/
function create_uri_object(&$object, $type=false) {
	$objtype = get_class($object);
	if (!$type) $type = URI_TYPE;
	switch ($type) {
		case "querystring":
			$creator = $objtype."URIQueryString";
			return new $creator($object);
			break;
		case "htaccess":
			$creator = $objtype."URIhtaccess";
			return new $creator($object);
			break;
		default:
			$creator = $objtype."URIWrapper";
			return new $creator($object);
	}
}
/*
class BlogURIFactory extends LnBlogObject {
	
	function BlogURIFactory(&$blog) {
		$this->object = $blog;
	}
	
	function doFactory($type=false) {
		if (!$type) $type = URI_TYPE;
		switch ($type) {
			case "querystring":
				return new BlogURIQueryString($this->object);
				break;
			case "htaccess":
				return new BlogURIhtaccess($this->object);
				break;
			default:
				return new BlogURIWrapper($this->object);
		}
	}
	
}
*/
class BlogURIWrapper extends LnBlogObject {
	
	function BlogURIWrapper(&$blog) {
		$this->object = $blog;
		$this->base_uri = localpath_to_uri($blog->home_path);
		$this->separator = "&amp;";
	}
	
	function setEscape($val) { $this->separator = $val ? "&amp;" : "&"; }
	
	function peramlink() { return $this->base_uri; }
	function base() { return $this->base_uri; }
	function blog() { return $this->base_uri; }
	function page() { return $this->base_uri; }
	
	function articles() { return $this->base_uri.BLOG_ARTICLE_PATH."/"; }
	function entries() { return $this->base_uri.BLOG_ENTRY_PATH."/"; }
	function archives() { return $this->base_uri.BLOG_ENTRY_PATH."/"; }
	function drafts() { return $this->base_uri.BLOG_DRAFT_PATH."/"; }
	function listdrafts() { return $this->base_uri.BLOG_DRAFT_PATH."/"; }
	
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
				$qs = $this->separator."$key=$val";
			}
		}
		return $this->base_uri."?action=$action".$qs;
	}
	
	function addentry() { return $this->wrapper("newentry"); }
	function addarticle() {
		return $this->wrapper("newentry", array("type"=>"article"));
	}
	function upload() { return $this->wrapper("upload"); }
	function edit() { return $this->wrapper("edit"); }
	function manage_reply() { return $this->wrapper("managereply"); }
	function login() { return $this->wrapper("login"); }
	function logout() { return $this->wrapper("logout"); }
	function editfile() { return $this->wrapper("editfile"); }
	function edituser() { return $this->wrapper("useredit"); }
	function pluginconfig() { return $this->wrapper("plugins"); }
	function pluginload() { return $this->wrapper("pluginload"); }
	function tags() { return $this->wrapper("tags"); }

}

class BlogURIhtaccess {
	
}

class BlogURIQueryString {
	
}

class BlogEntryURIWrapper extends LnBlogObject {
	function BlogEntryURIWrapper(&$ent) {
		$this->object = $ent;
		$this->base_uri = localpath_to_uri(dirname($ent->file));
		$this->separator = "&amp;";
	}
	
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
	
	function edit() {
		return $this->base_uri."?action=edit";
		/*
		$ent =& $this->object;
		if ($ent->isDraft()) {
			$entry_type = 'draft';
		} else {
			$entry_type = is_a($ent, 'Article') ? 'article' : 'entry';
		}
		return $this->base_uri."?$entry_type=".$ent->entryID();
		*/
		#$qs_arr['blog'] = $this->parentID();
		#$qs_arr[$entry_type] = $this->entryID();
		#return make_uri(INSTALL_ROOT_URL."pages/entryedit.php", 
		#                array("blog"     =>$this->parentID(), 
		#                      $entry_type=>$this->entryID()));
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
}

?>
