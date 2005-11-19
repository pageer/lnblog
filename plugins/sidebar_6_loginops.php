<?php 

class LoginOps extends Plugin {

	function Login() {
		$this->plugin_desc = _("Adds a control panel to the sidebar.");
		$this->plugin_version = "0.1.0";
	}

	function output($parm=false) {
		# Check if the user is logged in and, if so, present 
		# administrative options.
		$usr = NewUser();
		$blg = NewBlog();
		if (! $blg->isBlog()) return false;
		$root = $blg->getURL();
		if ($usr->checkLogin()) { 
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
<li><a href="<?php echo $root; ?>map.php"><?php p_("Edit custom sitemap"); ?></a></li>
<?php } ?>
<li><a href="<?php echo $root; ?>useredit.php"><?php p_("Edit User Information"); ?></a></li>
<li><a href="<?php echo $root; ?>logout.php"><?php pf_("Logout %s", $usr->username());; ?></a></li>
</ul>
<?php 
# If the user isn't logged in, give him a login link.
		} else { 
?>
<h3><a href="<?php echo $root; ?>login.php"><?php p_("Login"); ?></a></h3>
<?php 
		}  # End if statement
	}   # End function
	
}

$login = new LoginOps();
$login->registerEventHandler("sidebar", "OnOutput", "output");
?>
