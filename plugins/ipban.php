<?php 
class IPBan extends Plugin {
	
	function IPBan() {
		$this->plugin_desc = _("Allows you to ban IP addresses from adding comments or trackbacks.");
		$this->plugin_version = "0.2.0";
		$this->addOption("ban_list", _("File to store list of banned IPs."),
			"ip_ban.txt", "text");
		$this->getConfig();
	}

	# Write the ban list to disk.

	function updateList($add_list, $do_global=false) {
		$blog = NewBlog();
		$usr = NewUser();
		if ( $blog->isBlog() && $blog->canModifyBlog() && !$do_global) {
			$file = $blog->home_path.PATH_DELIM.$this->ban_list;
		} elseif ( $usr->checkLogin() && $usr->isAdministrator() ) {
			$file = USER_DATA_PATH.PATH_DELIM.$this->ban_list;
		} else {
			return false;
		}
		if (file_exists($file)) $list = file($file);
		else $list = array();
		$list = array_merge($list, $add_list);
		$list = array_unique($list);
		$content = '';
		foreach ($list as $ip) {
			if ($content != '') $content .= "\n";
			$content .= trim($ip);
		}
		$ret = write_file($file, $content);
	}

	function addBanLink(&$cmt) {
		$blog = NewBlog();
		$usr = NewUser();
		if ($blog->canModifyBlog()) {
			$cb_link = 
				spf_("IP: %s", $cmt->ip).
				' (<a href="?banip='.$cmt->ip.'" '.
				'onclick="return window.confirm(\''.
				spf_("Ban IP address %s will from submitting comments or trackbacks to this blog?", $cmt->ip).
				'\');">'._("Ban IP").'</a>) ';
				if ($usr->checkLogin() && $usr->isAdministrator()) {
					$cb_link .= '(<a href="?banip='.$cmt->ip.'&amp;global=yes" '.
					'onclick="return window.confirm(\''.
					spf_("Ban IP address %s will from submitting comments or trackbacks to this entire site?", $cmt->ip).
				'\');">'._("Global Ban").'</a>)';
				}
			$cmt->control_bar[] = $cb_link;
		}
	}

	# Ban an IP based on a query string.

	function banIP(&$param) {
		if (isset($_GET["banip"])) {
			$blog = NewBlog();
			#$this->cacheList();
			if ($blog->canModifyBlog()) {
				$ip = trim($_GET["banip"]);
				$global = isset($_GET["global"]);
				$this->updateList(array($ip), $global);
			}
		}
	}

	function checkBan($check_ip) {
		$blog = NewBlog();
		$local_list = array();
		$global_list = array();
		if ( $blog->isBlog() && 
		     file_exists($blog->home_path.PATH_DELIM.$this->ban_list)) {
			$local_list = file($blog->home_path.PATH_DELIM.$this->ban_list);
		}
		if (file_exists(USER_DATA_PATH.PATH_DELIM.$this->ban_list)) { 
			$global_list = file(USER_DATA_PATH.PATH_DELIM.$this->ban_list);
		}
		$banned_ips = array_merge($global_list, $local_list);
		$ban_post = false;
		foreach ($banned_ips as $item) {
			if (preg_match("/".trim($item)."/", trim($check_ip))) {
				$ban_post = true;
				break;
			}
		}
		return $ban_post;
	}

	# Sets the comment data to an empty string so that it cannot be added.
	function clearData(&$cmt) {
		if ($this->checkBan(trim($cmt->ip))) {
			$cmt->data = '';
			$cmt->subject = '';
			$cmt->email = '';
			$cmt->homepage = '';
			$cmt->name = '';
		}
	}

	# Sets the trackback URL to an empty string so that it cannot be added.
	function clearTBData(&$tb) {
		if ($this->checkBan(trim($tb->ip))) {
			$tb->url = false;
			$tb->data = '';
			$tb->title = '';
			$tb->blog = '';
		}
	}

}

$ban = new IPBan();
$ban->registerEventHandler("blogcomment", "OnOutput", "addBanLink");
$ban->registerEventHandler("blogcomment", "OnInsert", "clearData");
$ban->registerEventHandler("trackback", "OnOutput", "addBanLink");
$ban->registerEventHandler("trackback", "POSTRetreived", "clearTBData");
$ban->registerEventHandler("page", "OnOutput", "banIP");
?>
