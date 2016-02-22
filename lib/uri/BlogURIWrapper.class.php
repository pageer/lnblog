<?php

class BlogURIWrapper extends LnBlogObject {
	
	function BlogURIWrapper(&$blog) {
		$this->object = $blog;
		$this->base_uri = localpath_to_uri($blog->home_path);
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
	function login() { return $this->wrapper("login"); }
	function logout() { return $this->wrapper("logout"); }
	function editfile($args=null) {
		return $this->wrapper("editfile", $args);
	}
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