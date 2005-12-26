<?php 
class CommentBan extends Plugin {
	
	function CommentBan() {
		$this->plugin_desc = _("Allows you to ban users for comment spam.");
		$this->plugin_version = "0.1.0";
		$this->addOption("ban_list", _("File to store list of banned IPs."),
			"ip_ban.txt", "text");
		$this->getConfig();
	}

	# Handle caching of the contents of the ban list, so that we don't 
	# have to reload the file for every comment.
	function cache_list() {
		$banfile = BLOG_ROOT.PATH_DELIM.$this->ban_list;
		if (! file_exists($banfile)) 
			$banfile = USER_DATA_PATH.PATH_DELIM.$this->ban_list;
		if (! file_exists($banfile)) return true;
		$fsize = filesize($banfile);
		if (! isset($_SESSION["banfilesize"]) ||
		      $_SESSION["banfilesize"] != $fsize) {
			$_SESSION["banfilesize"] = $fsize;
			$_SESSION["banlist"] = file($banfile);
		}
	}

	function add_ban_link(&$cmt) {
		
	}

	# Sets the comment data to an empty string so that it cannot be added.
	function clear_data(&$cmt) {

	}

?>
