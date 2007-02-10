<?php
# Template: blog_admin_tpl.php
# This page contains the markup for the main administration page.
?>
<h1><?php pf_("%s System Administration", PACKAGE_NAME); ?></h1>
<form method="post" action="<?php echo $FORM_ACTION; ?>">
<h3><?php p_('Add Features'); ?></h3>
<ul>
<li><a href="plugin_setup.php"><?php p_("Configure site-wide plugins"); ?></a></li>
<li><a href="plugin_loading.php"><?php p_("Configure enabled plugins and load order"); ?></a></li>
<li><a href="pages/editfile.php?file=userdata/system.ini"><?php p_("Edit system.ini file"); ?></a></li>
<li><a href="pages/editfile.php?file=userdata/groups.ini"><?php p_("Edit groups.ini file"); ?></a></li>
<li><a href="pages/editfile.php?map=yes&amp;file=userdata/sitemap.htm&amp;list=yes"><?php p_("Modify site-wide menubar"); ?></a></li>
<li><a href="newlogin.php"><?php p_("Add new user"); ?></a></li>
<?php if (isset($SHOW_NEW)) { ?>
<li><a href="newblog.php"><?php p_("Add new blog"); ?></a></li>
<?php } ?>
</ul>
<h3><?php p_('Upgrade Functions'); ?></h3>
<ul>
<li>
<label for="upgrade"><?php p_("Upgrade blog to current version"); ?></label>
<select id="upgrade" name="upgrade">
<?php foreach ($BLOG_ID_LIST as $blog) { ?>
<option><?php echo $blog;?></option>
<?php } ?>
</select>
<input type="submit" id="upgrade_btn" name="upgrade_btn" value="<?php p_("Upgrade"); ?>" />
<?php if (isset($UPGRADE_STATUS)) { ?>
<p><?php pf_("Upgrade Status: %s", "<strong>".$UPGRADE_STATUS."</strong>"); ?></p>
<?php } ?>
</li>
<li>
<label for="register"><?php p_("Register a blog with the system"); ?></label>
<input type="text" id="register" name="register" />
<input type="submit" id="register_btn" name="register_btn" value="<?php p_("Register"); ?>" />
<?php if (isset($REGISTER_STATUS)) { ?>
<p><?php pf_("Registration Status: %s", "<strong>".$REGISTER_STATUS."</strong>"); ?></p>
<?php } ?>
</li>
<?php if (0) { /* Start commented-out code */ ?>
<li>
<label for="update"><?php p_("Update blog data"); ?></label>
<input type="text" id="update" name="update" />
<input type="submit" id="update_btn" name="update_btn" value="<?php p_("Update"); ?>" />
<?php if (isset($UPDATE_STATUS)) { ?>
<p><?php pf_("Upgrade Status: %s", "<strong>".$UPDATE_STATUS."</strong>"); ?></p>
<?php } ?>
</li>
<?php } /* End commented-out code */ ?>
<li>
<label for="fixperm"><?php p_("Fix directory permissions on blog"); ?></label>
<select id="fixperm" name="fixperm">
<?php foreach ($BLOG_ID_LIST as $blog) { ?>
<option><?php echo $blog;?></option>
<?php } ?>
</select>
<input type="submit" id="fixperm_btn" name="fixperm_btn" value="<?php p_("Fix Perms"); ?>" />
<?php if (isset($FIXPERM_STATUS)) { ?>
<p><?php pf_("Upgrade Status: %s", "<strong>".$FIXPERM_STATUS."</strong>"); ?></p>
<?php } ?>
</li>
</ul>
<ul>
<li><a href="bloglogout.php"><?php p_("Log out"); ?></a></li>
</ul>
</form>
