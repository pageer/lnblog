<h1><?php pf_("%s System Administration", PACKAGE_NAME); ?></h1>
<form method="post" action="<?php echo $FORM_ACTION; ?>">
<h3><?php p_('Add Features'); ?></h3>
<ul>
<li><a href="plugin_setup.php"><?php p_("Configure site-wide plugins"); ?></a></li>
<li><a href="plugin_loading.php"><?php p_("Configure enabled plugins and load order"); ?></a></li>
<li><a href="sitemap.php"><?php p_("Modify site-wide menubar"); ?></a></li>
<li><a href="newlogin.php"><?php p_("Add new user"); ?></a></li>
<?php if (isset($SHOW_NEW)) { ?>
<li><a href="newblog.php"><?php p_("Add new blog"); ?></a></li>
<?php } ?>
</ul>
<h3><?php p_('Upgrade Functions'); ?></h3>
<ul>
<li>
<label for="upgrade"><?php p_("Upgrade blog to current version"); ?></label><input type="text" id="<?php echo $UPGRADE; ?>" name="<?php echo $UPGRADE; ?>" /><input type="submit" id="ugbtn" name="ugbtn" value="<?php p_("Upgrade"); ?>" />
<?php if (isset($upgrade_status)) { ?>
<p><?php p_("Upgrade Status: %s", "<strong>".$upgrade_status."</strong>"); ?></p>
<?php } ?>
</li>
<li>
<label for="update"><?php p_("Update blog data"); ?></label><input type="text" id="<?php echo $UPDATE; ?>" name="<?php echo $UPDATE; ?>" /><input type="submit" id="udbtn" name="udbtn" value="<?php p_("Update"); ?>" />
</li>
<li>
<label for="fixperm"><?php p_("Fix directory permissions on blog"); ?></label><input type="text" id="<?php echo $PERMS; ?>" name="<?php echo $PERMS; ?>" /><input type="submit" id="fpbtn" name="fpbtn" value="<?php p_("Fix Perms"); ?>" />
<?php if (isset($upgrade_status)) { ?>
<p><?php p_("Upgrade Status: %s", "<strong>".$upgrade_status."</strong>"); ?></p>
<?php } ?>
</li>
</ul>
<ul>
<li><a href="bloglogout.php"><?php p_("Log out"); ?></a></li>
</ul>
</form>
