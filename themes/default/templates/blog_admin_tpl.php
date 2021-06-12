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

    $('.slide-down').hide();
    $('.slide-toggle').on('click', function () {
        $(this).parent().find('.slide-down').slideToggle();
        return false;
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
    <?php if (isset($SHOW_NEW)): ?>
    <li><a href="?action=newblog"><?php p_("Add new blog") ?></a></li>
    <li><a href="?action=import"><?php p_("Import blog") ?></a></li>
    <?php endif ?>
    <li><a href="?action=newlogin"><?php p_("Add new user") ?></a></li>
    <li>
        <a href="#" class="slide-toggle">Edit existing user</a>
        <div class="slide-down">
            <select id="username" name="username">
                <?php foreach ($USER_ID_LIST as $uid): ?>
                <option><?php echo $uid?></option>
                <?php endforeach ?>
            </select>
            <input type="submit" name="edituser" id="edituser" value="<?php p_("Edit")?>" />
        </div>
    </li>
</ul>
<h3><?php p_('Upgrade Functions') ?></h3>
<ul>
    <li>
        <a href="#" class="slide-toggle"><?php p_("Upgrade blog to current version") ?></a>
        <div class="slide-down">
            <select id="upgrade" name="upgrade">
                <?php foreach ($BLOG_ID_LIST as $blog): ?>
                <option><?php echo $blog?></option>
                <?php endforeach ?>
            </select>
            <input type="submit" id="upgrade_btn" name="upgrade_btn" value="<?php p_("Upgrade") ?>" />
        </div>
        <?php if (isset($UPGRADE_STATUS)): ?>
        <p><?php pf_("Upgrade Status: %s", "<strong>".$UPGRADE_STATUS."</strong>") ?></p>
        <?php endif ?>
    </li>
    <li>
        <a id="toggle-reg" class="slide-toggle" href="#regbox"><?php p_("Register a blog with the system") ?></a>
        <div id="regbox" class="registerbox slide-down">
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
        </div>
        <?php if (isset($REGISTER_STATUS)) { ?>
        <p><?php pf_("Registration Status: %s", "<strong>".$REGISTER_STATUS."</strong>") ?></p>
        <?php } ?>
    </li>
    <li>
        <a href="#" class="slide-toggle"><?php p_("Fix directory permissions on blog") ?></a>
        <div class="slide-down">
            <select id="fixperm" name="fixperm">
                <?php foreach ($BLOG_ID_LIST as $blog): ?>
                <option><?php echo $blog?></option>
                <?php endforeach ?>
            </select>
            <input type="submit" id="fixperm_btn" name="fixperm_btn" value="<?php p_("Fix Perms") ?>" />
        </div>
        <?php if (isset($FIXPERM_STATUS)): ?>
        <p><?php pf_("Upgrade Status: %s", "<strong>".$FIXPERM_STATUS."</strong>") ?></p>
        <?php endif ?>
    </li>
    <li>
        <a href="#" class="slide-toggle"><?php p_("Delete blog") ?></a>
        <div class="slide-down">
            <select id="delete" name="delete">
                <?php foreach ($BLOG_ID_LIST as $blog): ?>
                <option><?php echo $blog?></option>
                <?php endforeach ?>
            </select>
            <input type="submit" id="delete_btn" name="delete_btn" value="<?php p_("Delete") ?>" />
        </div>
        <?php if (isset($DELETE_STATUS)): ?>
        <p><?php pf_("Delete Status: %s", "<strong>".$DELETE_STATUS."</strong>") ?></p>
        <?php endif ?>
    </li>
</ul>
<h3><?php p_('Edit Configuration') ?></h3>
<ul>
    <li><a href="?action=plugins"><?php p_("Configure site-wide plugins") ?></a></li>
    <li><a href="?action=pluginload"><?php p_("Configure enabled plugins and load order") ?></a></li>
    <li><a href="?action=editfile&target=userdata&file=system.ini"><?php p_("Edit system.ini file") ?></a></li>
    <li><a href="?action=editfile&target=userdata&file=groups.ini"><?php p_("Edit groups.ini file") ?></a></li>
    <li><a href="?action=editfile&map=yes&target=userdata&file=sitemap.htm&list=yes"><?php p_("Modify site-wide menubar") ?></a></li>
</ul>
</form>
