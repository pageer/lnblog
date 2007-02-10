<?php 

# Plugin: IPBan
# Bans certain IP addresses from posting comments or trackbacks.
#
# This plugin works by merging two lists of IP addresses.  Both of these 
# files have the same name (which is configurable as an option for the 
# plugin), but one is located in the root of the blog and the other is in
# the LnBlog userdata folder.  The file format is one IP address per line.
# When the plugin runs, it reads both of these files (if they exist) and
# merges their contents.  It then rejects attempts to post comments or 
# trackbacks from any of the IP addresses in this list.
#
# Note that the IP addresses are actually interpreted as Perl-Compatible
# Regular Expressions.  This means that you can use regular expression syntax
# to ban ranges of IP addresses with a single entry.  One example might be to
# ban a subnet with an entry like this
# | 12.34.56.*
#
# There are two ways to add IP addresses to the ban list.  One is through 
# links in the footers of comments and trackbacks.  Clicking these links will
# add the exact IP address of the comment/trackback to the ban list.
# The other method is by directly editing the files using the sidebar links.
# This is the method required if you want to ban ranges of IP addresses.

class IPBan extends Plugin {
	
	function IPBan() {
		$this->plugin_desc = _("Allows you to ban IP addresses from adding comments or trackbacks.");
		$this->plugin_version = "0.2.4";
		$this->addOption("ban_list", _("File to store list of banned IPs."),
			"ip_ban.txt", "text");
		$this->addOption("admin_local", _("Show per-blog ban link when administrator"), false, "checkbox");
		$this->addOption("ban_del", _("Ban link both bans IP and deletes"), true, "checkbox");
		$this->getConfig();
		
		# Call banIP() here so that it will get called on pages that never output, 
		# e.g. when you do a JavaScript confirmation on a "delete and ban", which 
		# redirects instead of outputing the page.	
		$this->banIP($this);
	}

	# Write the ban list to disk.

	function updateList($add_list, $do_global=false) {
		global $SYSTEM;
		$blog = NewBlog();
		$usr = NewUser();
		if ( $blog->isBlog() && $SYSTEM->canModify($blog, $usr) && !$do_global) {
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
		sort($list);
		$content = '';
		foreach ($list as $ip) {
			if ($content != '') $content .= "\n";
			$content .= trim($ip);
		}
		$ret = write_file($file, $content);
	}

	function addBanLink(&$cmt) {
		global $SYSTEM;
		$blog = NewBlog();
		$usr = NewUser();
		if ($SYSTEM->canModify($blog, $usr) && $usr->checkLogin()) {
			$cb_link = 
				spf_("IP: %s", $cmt->ip);
			if ($this->ban_del) {
				$cb_link_loc =
					' (<a href="'.make_uri($cmt->uri("delete"), array('banip'=>$cmt->ip)).'" '.
					'onclick="this.href = this.href + \'&conf=yes\'; return window.confirm(\''.
					spf_("Delete %s and ban IP address %s from submitting comments or trackbacks to this blog?", $cmt->getAnchor(), $cmt->ip).
					'\');">'._("Delete &amp; Ban IP").'</a>) ';
				$cb_link_glob = ' (<a href="'.
					make_uri($cmt->uri("delete"),array('banip'=>$cmt->ip, 'global'=>'yes')).'" '.
					'onclick="this.href = this.href + \'&conf=yes\'; return window.confirm(\''.
					spf_("Delete %s and ban IP address %s from submitting comments or trackbacks to this entire site?", $cmt->getAnchor(), $cmt->ip).
					'\');">'._("Delete &amp; Ban Globally").'</a>)';
			} else {
				$cb_link_loc =
					' (<a href="'.make_uri(false, array('banip'=>$cmt->ip)).'" '.
					'onclick="return window.confirm(\''.
					spf_("Ban IP address %s from submitting comments or trackbacks to this blog?", $cmt->ip).
					'\');">'._("Ban IP").'</a>) ';
				$cb_link_glob = ' (<a href="'.
					make_uri(false,array('banip'=>$cmt->ip, 'global'=>'yes')).
					'onclick="return window.confirm(\''.
					spf_("Ban IP address %s from submitting comments or trackbacks to this entire site?", 	$cmt->ip).
					'\');">'._("Global Ban").'</a>)';
			}

			if ($this->checkBan($cmt->ip) || $cmt->ip == GET('banip')) {
				$cb_link .= _(" (Banned)");
			} elseif ($usr->checkLogin() && $usr->isAdministrator()) {
				if ($this->admin_local) {
					$cb_link .= $cb_link_loc.$cb_link_glob;
				} else {
					$cb_link .= $cb_link_glob;
				}
			} else {
				$cb_link .= $cb_link_loc;
			}
			
			$cmt->control_bar[] = $cb_link;
		}
	}

	# Ban an IP based on a query string.

	function banIP(&$param) {
		global $SYSTEM;
		if (isset($_GET["banip"])) {
			$blog = NewBlog();
			$usr = NewUser();
			if ($SYSTEM->canModify($blog, $usr) && $usr->checkLogin()) {
				$ip = trim($_GET["banip"]);
				$global = isset($_GET["global"]);
				$this->updateList(array($ip), $global);
				#$param->redirect(make_uri());
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
			if (preg_match("/^".trim($item)."$/", trim($check_ip))) {
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

	function sidebarLink($param) {
		global $PLUGIN_MANAGER;
		$blg = NewBlog();
		$usr = NewUser();
		$banfile = $PLUGIN_MANAGER->plugin_config->value("ipban", 
		                                 "ban_list", "ip_ban.txt");
		echo '<li><a href="'.$blg->uri('editfile', "file=$banfile").'">'.
			_("Blog IP blacklist").'</a></li>';
		if ($usr->isAdministrator()) {
			echo '<li><a href="'.
				make_uri(INSTALL_ROOT_URL.'pages/editfile.php',
				         array('file'=>'userdata/'.$banfile)).
				'">'._("Global IP blacklist").'</a></li>';
		}
	}

}

$ban =& new IPBan();
$ban->registerEventHandler("blogcomment", "OnOutput", "addBanLink");
$ban->registerEventHandler("blogcomment", "OnInsert", "clearData");
$ban->registerEventHandler("trackback", "OnOutput", "addBanLink");
$ban->registerEventHandler("trackback", "POSTRetreived", "clearTBData");
$ban->registerEventHandler("loginops", "PluginOutput", "sidebarLink");
$ban->registerEventHandler("page", "OnOutput", "banIP");
?>
