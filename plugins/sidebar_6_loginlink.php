<?php 

class LoginLink extends Plugin {

	function LoginLink() {
		$this->plugin_desc = _("Adds login panel to the sidebar.");
		$this->plugin_version = "0.2.0";
		$this->use_form = false;
		$this->admin_link = false;
		$this->member_list = array();
		$this->member_list["use_form"] =
			array("description"=>_("Use a login form, instead of a link to the login page"),
			      "default"=>false,
			      "control"=>"checkbox");
		$this->member_list["admin_link"] =
			array("description"=>_("Show link to administration pages"),
			      "default"=>false,
			      "control"=>"checkbox");
		$this->getConfig();
	}

	function output($parm=false) {
		# Check if the user is logged in and, if so, present 
		# administrative options.
		$usr = NewUser();
		$blg = NewBlog();
		if (! $blg->isBlog()) return false;
		$root = $blg->getURL();
		if ($usr->checkLogin()) return false;

		if ($this->use_form) {
?>
<h3><?php p_("Login"); ?></h3>
<fieldset style="border: 0">
<form method="post" action="<?php echo $root."login.php"; ?>">
<div>
<label for="user"><?php p_("Username"); ?></label>
<input name="user" id="user" type="text" />
</div>
<div>
<label for="passwd"><?php p_("Password"); ?></label>
<input name="passwd" id="passwd" type="password" />
</div>
<input type="submit" value="<?php p_("Login"); ?>" />
</form>
</fieldset>
<?php 
		} else {
?>
<p style="margin: 5%"><strong><a href="<?php echo $root; ?>login.php"><?php p_("User Login"); ?></a></strong></p>
<?php
		}
		if ($this->admin_link) {
?>
<p style="margin: 5%"><a href="<?php echo INSTALL_ROOT_URL; ?>"><?php pf_("%s Administration", PACKAGE_NAME); ?></a></p>
<?php
		}
	}   # End function
	
}

$login = new LoginLink();
$login->registerEventHandler("sidebar", "OnOutput", "output");
?>
