<?php
# Template: blog_admin_tpl.php
# This page contains the markup for the main administration page.
?>
<style>
.registerbox {
    margin-left: 2em;
}
.registerbox div {
    margin-bottom: 4px;
}
.registerbox label {
    display: inline-block;
    width: 6em;
    text-align: right;
}
.registerbox input[type="text"] {
    width: 50%;
}
.registerbox #register_btn {
    margin-left: 8em;
}
</style>
<script type="application/javascript">
$(document).ready(function () {
    var default_path = '<?php echo $DEFAULT_PATH?>';
    var default_url = '<?php echo $DEFAULT_URL?>';
    var path_sep = '<?php echo $PATH_SEP?>';

    $('#toggle-reg').on('click', function () {
        $('#regbox').toggle();
    });

    $('#register').on('change', function () {
        var path = default_path + path_sep + $(this).val().replace('/', path_sep) + path_sep;
        $('#register_path').val(path);
        $('#register_url').val(default_url + $(this).val() + '/');
    });
});
</script>
<h1><?php pf_("%s System Administration", PACKAGE_NAME) ?></h1>
<form method="post" action="<?php echo $FORM_ACTION ?>">
<?php $this->outputCsrfField() ?>
<h3><?php p_('Add Features') ?></h3>
<ul>
<li><a href="?action=plugins"><?php p_("Configure site-wide plugins") ?></a></li>
<li><a href="?action=pluginload"><?php p_("Configure enabled plugins and load order") ?></a></li>
<li><a href="?action=editfile&target=userdata&file=system.ini"><?php p_("Edit system.ini file") ?></a></li>
<li><a href="?action=editfile&target=userdata&file=groups.ini"><?php p_("Edit groups.ini file") ?></a></li>
<li><a href="?action=editfile&map=yes&target=userdata&file=sitemap.htm&list=yes"><?php p_("Modify site-wide menubar") ?></a></li>
<li><a href="?action=newlogin"><?php p_("Add new user") ?></a></li>
<li>Edit existing user
<select id="username" name="username">
<?php foreach ($USER_ID_LIST as $uid): ?>
<option><?php echo $uid?></option>
<?php endforeach ?>
</select>
<input type="submit" name="edituser" id="edituser" value="<?php p_("Edit")?>" />
</li>
<?php if (isset($SHOW_NEW)): ?>
<li><a href="?action=import"><?php p_("Import blog") ?></a></li>
<li><a href="?action=newblog"><?php p_("Add new blog") ?></a></li>
<?php endif ?>
</ul>
<h3><?php p_('Upgrade Functions') ?></h3>
<ul>
<li>
<label for="upgrade"><?php p_("Upgrade blog to current version") ?></label>
<select id="upgrade" name="upgrade">
<?php foreach ($BLOG_ID_LIST as $blog): ?>
<option><?php echo $blog?></option>
<?php endforeach ?>
</select>
<input type="submit" id="upgrade_btn" name="upgrade_btn" value="<?php p_("Upgrade") ?>" />
<?php if (isset($UPGRADE_STATUS)): ?>
<p><?php pf_("Upgrade Status: %s", "<strong>".$UPGRADE_STATUS."</strong>") ?></p>
<?php endif ?>
</li>
<li>
<label for="register"><?php p_("Register a blog with the system") ?></label>
<a id="toggle-reg" href="#regbox">[<?php p_('Toggle')?>]</a>
<div id="regbox" class="registerbox">
    <div>
        <label for="register"><?php p_("Blog ID") ?></label>
        <input type="text" id="register" name="register" />
    </div>
    <div>
        <label for="register"><?php p_("Blog path") ?></label>
        <input type="text" id="register_path" name="register_path" />
    <div>
    </div>
        <label for="register"><?php p_("Blog URL") ?></label>
        <input type="text" id="register_url" name="register_url" />
    </div>
    <input type="submit" id="register_btn" name="register_btn" value="<?php p_("Register") ?>" />
    <?php if (isset($REGISTER_STATUS)) { ?>
    <p><?php pf_("Registration Status: %s", "<strong>".$REGISTER_STATUS."</strong>") ?></p>
    <?php } ?>
</div>
</li>
<li>
<label for="fixperm"><?php p_("Fix directory permissions on blog") ?></label>
<select id="fixperm" name="fixperm">
<?php foreach ($BLOG_ID_LIST as $blog): ?>
<option><?php echo $blog?></option>
<?php endforeach ?>
</select>
<input type="submit" id="fixperm_btn" name="fixperm_btn" value="<?php p_("Fix Perms") ?>" />
<?php if (isset($FIXPERM_STATUS)): ?>
<p><?php pf_("Upgrade Status: %s", "<strong>".$FIXPERM_STATUS."</strong>") ?></p>
<?php endif ?>
</li>

<li>
<label for="delete"><?php p_("Delete blog") ?></label>
<select id="delete" name="delete">
<?php foreach ($BLOG_ID_LIST as $blog): ?>
<option><?php echo $blog?></option>
<?php endforeach ?>
</select>
<input type="submit" id="delete_btn" name="delete_btn" value="<?php p_("Delete") ?>" />
<?php if (isset($DELETE_STATUS)): ?>
<p><?php pf_("Delete Status: %s", "<strong>".$DELETE_STATUS."</strong>") ?></p>
<?php endif ?>
</li>

</ul>
<ul>
<li><a href="?action=logout"><?php p_("Log out") ?></a></li>
</ul>
</form>
