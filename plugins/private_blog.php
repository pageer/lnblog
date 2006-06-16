<?php
# Allows you to make a blog private, i.e. so that it can only be accessed
# by a predefined list of users.
class PrivateBlog extends Plugin {
	function PrivateBlog() {
		$this->plugin_desc = _("Makes a blog private so that only certain users can access it.");
		$this->plugin_version = "0.1.0";
		$this->addOption("userlist", _("Allowed user names (comma separated)"), "");
		$this->getConfig();
	}

	function check_allow(&$param) {
		global $SYSTEM;
		$blog = NewBlog();
		$usr = NewUser();
		if (! $blog->isBlog() || 
		    $SYSTEM->canModify($blog, $usr) ||
			! $this->userlist ||
			current_file() == "login.php") return false;
		if (! $usr->checkLogin()) $param->redirect($blog->uri('login'));
		$users = explode(",",$this->userlist);
		foreach ($users as $item) {
			if ($usr->username() == trim($item)) return true;
		}
		$param->redirect($blog->uri('login'));
	}
}

$plug = new PrivateBlog();
$plug->registerEventHandler("page", "OnOutput", "check_allow");
?>
