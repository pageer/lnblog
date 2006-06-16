<?php 

class LoginOps extends Plugin {

	function LoginOps() {
		$this->plugin_desc = _("Adds a control panel to the sidebar.");
		$this->plugin_version = "0.2.0";
	}

	function output($parm=false) {
		global $SYSTEM;
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
<?php if ($SYSTEM->canAddTo($blg, $usr)) { ?>
<li><a href="<?php echo $blg->uri('addentry'); ?>"><?php p_("Add new post"); ?></a></li>
<?php } 
      if ($SYSTEM->canAddTo($blg, $usr)) { ?> 
<li><a href="<?php echo $blg->uri('addarticle'); ?>"><?php p_("Add new article"); ?></a></li>
<?php }
      if ($SYSTEM->canModify($blg,$usr)) { ?>
<li><a href="<?php echo $blg->uri('upload'); ?>"><?php p_("Upload file for blog"); ?></a></li>
<li><a href="<?php echo $blg->uri('edit'); ?>"><?php p_("Edit weblog settings"); ?></a></li>
<?php } ?>
<li><a href="<?php echo $blg->uri('edituser'); ?>"><?php p_("Edit User Information"); ?></a></li>
<?php if ($usr->isAdministrator()) { ?>
<li><a href="<?php echo INSTALL_ROOT_URL; ?>"><?php p_("Site administration"); ?></a></li>
<?php } ?>
<li><a href="<?php echo $blg->uri('logout'); ?>"><?php pf_("Logout %s", $usr->username());; ?></a></li>
</ul>
<?php if ($SYSTEM->canModify($blg,$usr)) { ?>
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

$login = new LoginOps();
#$login->addEvent
$login->registerEventHandler("sidebar", "OnOutput", "output");
?>
