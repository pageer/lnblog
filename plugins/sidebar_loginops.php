<?php
# Plugin: LoginOps
# This plugin handles displaying the administrative panels in the sidebar.
# This includes the links to create entries, manage plugins, etc.  You would
# pretty much never want to disable this.

class LoginOps extends Plugin
{

    function __construct($do_output=0) {
        $this->plugin_desc = _("Adds a control panel to the sidebar.");
        $this->plugin_version = "0.2.2";
        $this->addNoEventOption();

        parent::__construct();

        $this->registerNoEventOutputHandler("sidebar", "output");
        $this->registerNoEventOutputCompleteHandler("sidebar", "outputend");

        if ($do_output) {
            $this->output();
        }
    }

    function output($parm=false) {
        # Check if the user is logged in and, if so, present
        # administrative options.
        $usr = NewUser();
        $blg = NewBlog();

        $show_admin = !$blg->isBlog() && $usr->isAdministrator();
        $show_nothing = !$blg->isBlog() || !$usr->checkLogin();
        $root = $blg->getURL();

        if (!$usr->checkLogin() || (!$blg->isBlog() && !$usr->isAdministrator())) {
            return false;
        }
        ?>
<?php if ($show_admin): ?>
<h3><?php p_("System Administration");?></h3>
<ul>
    <li><a href="<?php echo INSTALL_ROOT_URL;?>"><?php p_("Back to main menu");?></a></li>
    <li><a href="?action=logout"><?php p_("Log out") ?></a></li>
</ul>
<?php else: ?>

<h3><?php p_("Weblog Administration"); ?></h3>
<ul>
    <?php if (System::instance()->canAddTo($blg, $usr)): ?>
    <li><a href="<?php echo $blg->uri('addentry'); ?>"><?php p_("New post"); ?></a></li>
    <?php endif; ?>

    <?php if (System::instance()->canModify($blg, $usr)): ?>
    <li><a href="<?php echo $blg->uri('listdrafts');?>"><?php p_("Drafts");?></a></li>
    <?php endif; ?>

    <?php if (System::instance()->canModify($blg, $usr)): ?>
    <li><a href="<?php echo $blg->uri('manage_reply');?>"><?php p_("Manage replies");?></a></li>
    <?php endif; ?>

    <?php if (System::instance()->canModify($blg, $usr)): ?>
    <li><a href="<?php echo $blg->uri('upload'); ?>"><?php p_("Upload file for blog"); ?></a></li>
    <li><a href="<?php echo $blg->uri('edit'); ?>"><?php p_("Edit weblog settings"); ?></a></li>
    <?php endif; ?>

    <li><a href="<?php echo $blg->uri('edituser'); ?>"><?php p_("Edit User Information"); ?></a></li>

    <?php if ($usr->isAdministrator()): ?>
    <li><a href="<?php echo INSTALL_ROOT_URL; ?>"><?php p_("Site administration"); ?></a></li>
    <?php endif; ?>

    <li><a href="<?php echo $blg->uri('logout'); ?>"><?php pf_("Logout %s", $usr->username()) ?></a></li>

    <?php if ($blg->sw_version < REQUIRED_VERSION): ?>
    <li style="font-weight: bold; font-style: italic; margin-top: 10px;">
        <?php pf_("Blog is at version %s.  Update required.", $blg->sw_version)?>
    </li>
    <?php endif ?>
</ul>
<?php endif; ?>
<?php if (System::instance()->canModify($blg, $usr)): ?>
<h3>Plugin Configuration</h3>
<ul>
    <li><a href="<?php echo $blg->uri('pluginconfig');?>"><?php p_("Configure plugins"); ?></a></li>
    <li><a href="<?php echo $blg->uri('pluginload');?>"><?php p_("Plugin loading"); ?></a></li>
    <?php $this->raiseEvent("PluginOutput"); ?>
</ul>
<?php endif; ?>
<?php
    }   # End function

    public function outputend($param = false) {
        $usr = NewUser();
        $blg = NewBlog();
        if (! $blg->isBlog()) {
            return false;
        }
        $root = $blg->getURL();
        if ($usr->checkLogin()) {
            return false;
        }
        ?>
<p class="login-link"><a href="<?php echo $blg->uri('login'); ?>"><?php p_("User Login"); ?></a></p>
        <?php
    }
}

if (! PluginManager::instance()->plugin_config->value('loginops', 'creator_output', 0)) {
    $plug = new LoginOps();
}
