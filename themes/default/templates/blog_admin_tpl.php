<h1><?php echo PACKAGE_NAME; ?> System Administration</h1>
<form method="post" action="<?php echo $FORM_ACTION; ?>">
<ul>
<li><a href="sitemap.php">Modify site-wide menubar</a></li>
<li><a href="newlogin.php">Add new user</a></li>
<?php if (isset($SHOW_NEW)) { ?>
<li><a href="newblog.php">Add new blog</a></li>
<?php } ?>
<li>
<label for="update">Update blog data</label><input type="text" id="<?php echo $UPDATE; ?>" name="<?php echo $UPDATE; ?>" /><input type="submit" id="udbtn" name="udbtn" value="Update" />
</li>
<li>
<label for="upgrade">Upgrade blog to current version</label><input type="text" id="<?php echo $UPGRADE; ?>" name="<?php echo $UPGRADE; ?>" /><input type="submit" id="ugbtn" name="ugbtn" value="Upgrade" />
<?php if (isset($upgrade_status)) { ?>
<p>Upgrade Status: <strong><?php echo $upgrade_status; ?></strong></p>
<?php } ?>
</li>
<li>
<label for="fixperm">Fix directory permissions on blog</label><input type="text" id="<?php echo $PERMS; ?>" name="<?php echo $PERMS; ?>" /><input type="submit" id="fpbtn" name="fpbtn" value="Fix Perms" />
<?php if (isset($upgrade_status)) { ?>
<p>Upgrade Status: <strong><?php echo $upgrade_status; ?></strong></p>
<?php } ?>
</li>
<li><a href="bloglogout.php">Log out</a></li>
</ul>
</form>
