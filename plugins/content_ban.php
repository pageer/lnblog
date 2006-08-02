<?php 

# Plugin: ContentBan
# Ban posts or trackbacks based on whether or not they match one or more
# of a list of PCREs (Perl-Compatible Regular Expressions).
#
# This plugin is similar to the IPBan plugin, in that it uses two ban lists,
# one per-blog list and one global list.  Each lists is a plain text file
# that is formatted as one PCRE per line.  Each line must be a full PCRE 
# match expression, including delimiters and options.  So, for example,
# you might ban posts containing references to Viagra with an expression 
# like this
# | /viagra/i
#
# New expressions are added by editing the the ban files using the sidebar
# links.  Note that there is no syntax checking done on the files when they
# are edited, so you will get no warning if you enter a bad regex.  The only
# indication will be an error message displayed when someone attempts to post
# a comment or trackback.

class ContentBan extends Plugin {
	
	function ContentBan() {
		$this->plugin_desc = _("Allows you to ban comments or trackbacks that match certain regular expressions.");
		$this->plugin_version = "0.1.0";
		$this->addOption("ban_list", _("File to store list of banned regular expressions (one RE per line)."),
			"re_ban.txt", "text");
		$this->addOption("user_exempt",
		                 _("Do not apply ban to logged-in users."),
		                 false, "checkbox");
		$this->getConfig();
	}

	# Write the ban list to disk.

	function checkBan(&$obj) {
		$blog = NewBlog();
		$usr = NewUser();
		$local_list = array();
		$global_list = array();

		if ($this->user_exempt && $usr->checkLogin()) return false;
		
		if ( $blog->isBlog() && 
		     file_exists($blog->home_path.PATH_DELIM.$this->ban_list)) {
			$local_list = file($blog->home_path.PATH_DELIM.$this->ban_list);
		}
		if (file_exists(USER_DATA_PATH.PATH_DELIM.$this->ban_list)) { 
			$global_list = file(USER_DATA_PATH.PATH_DELIM.$this->ban_list);
		}
		$banned_res = array_merge($global_list, $local_list);
		$ban_post = false;
		if (strtolower(get_class($obj)) == "blogcomment") {
			$fields = array("data","subject","email","url","name");
		} else {
			$fields = array("data","url","title","blog");
		}
		foreach ($banned_res as $item) {
			foreach ($fields as $fld) {
				if (preg_match(trim($item), trim($obj->$fld))) {
					$ban_post = true;
					break;
				}
			}
		}
		return $ban_post;
	}

	# Sets the comment data to an empty string so that it cannot be added.
	function clearData(&$cmt) {
		if ($this->checkBan($cmt)) {
			$cmt->data = '';
			$cmt->subject = '';
			$cmt->email = '';
			$cmt->url = '';
			$cmt->name = '';
		}
	}

	# Sets the trackback URL to an empty string so that it cannot be added.
	function clearTBData(&$tb) {
		if ($this->checkBan($tb)) {
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
		$banfile = $this->ban_list;
		echo '<li><a href="'.$blg->uri('editfile', "file=$banfile").'">'.
			_("Blog RegEx blacklist").'</a></li>';
		if ($usr->isAdministrator()) {
			echo '<li><a href="'.
				make_uri(INSTALL_ROOT_URL.'pages/editfile.php',
				         array('file'=>'userdata/'.$banfile)).'">'.
				_("Global RegEx blacklist").'</a></li>';
		}
	}

}

$ban =& new ContentBan();
$ban->registerEventHandler("blogcomment", "OnInsert", "clearData");
$ban->registerEventHandler("trackback", "POSTRetreived", "clearTBData");
$ban->registerEventHandler("loginops", "PluginOutput", "sidebarLink");
?>
