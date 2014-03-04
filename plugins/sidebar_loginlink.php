<?php 

class LoginLink extends Plugin {

	function LoginLink($do_output=0) {
		global $SYSTEM;
		$this->plugin_desc = _("Adds login panel to the sidebar.");
		$this->plugin_version = "0.3.0";
		$this->use_form = false;
		$this->admin_link = false;
		$this->addOption("use_form",
			_("Use a login form, instead of a link to the login page"),
			false, "checkbox");
		$this->addOption("admin_link",
			_("Show link to administration pages"),
			false, "checkbox");
		$this->addOption('no_event',
			_('No event handlers - do output when plugin is created'),
			$SYSTEM->sys_ini->value("plugins","EventDefaultOff", 0), 
			'checkbox');

		$this->getConfig();

		if ( $this->no_event || 
		     $SYSTEM->sys_ini->value("plugins","EventForceOff", 0) ) {
			# If either of these is true, then don't set the event handler
			# and rely on explicit invocation for output.
		} else {
			$this->registerEventHandler("sidebar", "OnOutput", "output");
		}
		
		if ($do_output) $this->output();
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
<form method="post" action="<?php echo $blg->uri('login'); ?>">
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
<p class="login-link"><a href="<?php echo $blg->uri('login'); ?>"><?php p_("User Login"); ?></a></p>
<?php
		}
		if ($this->admin_link) {
?>
<p style="margin: 5%"><a href="<?php echo INSTALL_ROOT_URL; ?>"><?php pf_("%s Administration", PACKAGE_NAME); ?></a></p>
<?php
		}
	}   # End function
	
}

global $PLUGIN_MANAGER;
if (! $PLUGIN_MANAGER->plugin_config->value('loginlink', 'creator_output', 0)) {
	$plug = new LoginLink();
}
?>
