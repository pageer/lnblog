<?php
# Plugin: LoginOps
# This plugin handles displaying the administrative panels in the sidebar.
# This includes the links to create entries, manage plugins, etc.  You would
# pretty much never want to disable this.

class LoginOps extends Plugin {

	function __construct($do_output=0) {
		$this->plugin_desc = _("Adds a control panel to the sidebar.");
		$this->plugin_version = "0.2.2";
		$this->addOption('no_event',
			_('No event handlers - do output when plugin is created'),
			System::instance()->sys_ini->value("plugins","EventDefaultOff", 0), 
			'checkbox');

		parent::__construct();

		if ( $this->no_event || 
		     System::instance()->sys_ini->value("plugins","EventForceOff", 0) ) {
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
		if (! $usr->checkLogin()) return false; 
		if (! $blg->isBlog() && $usr->isAdministrator()) { ?>
<h3><?php p_("System Administration");?></h3>
<ul>
<li><a href="<?php echo INSTALL_ROOT_URL;?>"><?php p_("Back to main menu");?></a></li>
</ul>
		<?php 
			return true;
		}elseif (! $blg->isBlog()) return false;
		$root = $blg->getURL();
?>
<h3><?php p_("Weblog Administration"); ?></h3>
<ul>
<?php if (System::instance()->canAddTo($blg, $usr)) { ?>
<li><a href="<?php echo $blg->uri('addentry'); ?>"><?php p_("Add new post"); ?></a></li>
<?php } 
      if (System::instance()->canAddTo($blg, $usr)) { ?> 
<li><a href="<?php echo $blg->uri('addarticle'); ?>"><?php p_("Add new article"); ?></a></li>
<?php }
      if (System::instance()->canModify($blg,$usr)) { ?>
<li><a href="<?php echo $blg->uri('listdrafts');?>"><?php p_("Edit drafts");?></a></li>
<?php }
      if (System::instance()->canModify($blg,$usr)) { ?>
<li><a href="<?php echo $blg->uri('manage_reply');?>"><?php p_("Manage replies");?></a></li>
<?php }
      if (System::instance()->canModify($blg,$usr)) { ?>
<li><a href="<?php echo $blg->uri('upload'); ?>"><?php p_("Upload file for blog"); ?></a></li>
<li><a href="<?php echo $blg->uri('edit'); ?>"><?php p_("Edit weblog settings"); ?></a></li>
<?php } ?>
<li><a href="<?php echo $blg->uri('edituser'); ?>"><?php p_("Edit User Information"); ?></a></li>
<?php if ($usr->isAdministrator()) { ?>
<li><a href="<?php echo INSTALL_ROOT_URL; ?>"><?php p_("Site administration"); ?></a></li>
<?php } ?>
<li><a href="<?php echo $blg->uri('logout'); ?>"><?php pf_("Logout %s", $usr->username());; ?></a></li>
<?php if ($blg->sw_version < REQUIRED_VERSION) { ?>
<li><?php pf_("Blog is at version %s.  Update required.", $blg->sw_version);?></li>
<?php } ?>
</ul>
<?php if (System::instance()->canModify($blg,$usr)) { ?>
<h3>Plugin Configuration</h3>
<ul>
<li><a href="<?php echo $blg->uri('pluginconfig');?>"><?php p_("Configure plugins"); ?></a></li>
<li><a href="<?php echo $blg->uri('pluginload');?>"><?php p_("Plugin loading"); ?></a></li>
<?php $this->raiseEvent("PluginOutput"); ?>
</ul>
<?php } ?>
<?php 
	}   # End function
	
}

if (! PluginManager::instance()->plugin_config->value('loginops', 'creator_output', 0)) {
	$plug = new LoginOps();
}
