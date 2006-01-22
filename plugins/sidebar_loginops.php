<?php 

class LoginOps extends Plugin {

	function LoginOps() {
		$this->plugin_desc = _("Adds a control panel to the sidebar.");
		$this->plugin_version = "0.1.0";
	}

	function output($parm=false) {
		# Check if the user is logged in and, if so, present 
		# administrative options.
		$usr = NewUser();
		$blg = NewBlog();
		if (! $usr->checkLogin()) return false; 
		if (! $blg->isBlog() && $usr->checkLogin(ADMIN_USER)) { ?>
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
<?php if ($blg->canAddEntry()) { ?>
<li><a href="<?php echo $root; ?>new.php"><?php p_("Add new post"); ?></a></li>
<?php } 
      if ($blg->canAddArticle()) { ?> 
<li><a href="<?php echo $root; ?>newart.php"><?php p_("Add new article"); ?></a></li>
<?php }
      if ($blg->canModifyBlog()) { ?>
<li><a href="<?php echo $root; ?>uploadfile.php"><?php p_("Upload file for blog"); ?></a></li>
<li><a href="<?php echo $root; ?>edit.php"><?php p_("Edit weblog settings"); ?></a></li>
<?php if (class_exists("sitemap")) { ?>
<li><a href="<?php echo $root; ?>map.php"><?php p_("Edit custom sitemap"); ?></a></li>
<?php } ?>
<?php if (class_exists("ipban")) { 
	global $PLUGIN_MANAGER;
	$banfile = $PLUGIN_MANAGER->plugin_config->value("ipban", "ban_list", "ip_ban.txt");
?><li><a href="<?php echo INSTALL_ROOT_URL; ?>editfile.php?blog=<?php echo $blg->blogid; ?>&amp;file=<?php echo $banfile; ?>"><?php p_("Edit IP blacklist"); ?></a></li><?php
	if ($usr->isAdministrator()) { ?>
<li><a href="<?php echo INSTALL_ROOT_URL; ?>editfile.php?file=userdata/<?php echo $banfile; ?>"><?php p_("Edit global blacklist"); ?></a></li>
<?php 
	} 
}?>
<li><a href="<?php echo $root; ?>plugins.php"><?php p_("Configure plugins"); ?></a></li>
<li><a href="<?php echo $root; ?>pluginload.php"><?php p_("Configure plugin loading"); ?></a></li>
<?php } ?>
<li><a href="<?php echo $root; ?>useredit.php"><?php p_("Edit User Information"); ?></a></li>
<?php if ($usr->username() == ADMIN_USER) { ?>
<li><a href="<?php echo INSTALL_ROOT_URL; ?>"><?php p_("Site administration"); ?></a></li>
<?php } ?>
<li><a href="<?php echo $root; ?>logout.php"><?php pf_("Logout %s", $usr->username());; ?></a></li>
</ul>
<?php 
	}   # End function
	
}

$login = new LoginOps();
$login->registerEventHandler("sidebar", "OnOutput", "output");
?>
