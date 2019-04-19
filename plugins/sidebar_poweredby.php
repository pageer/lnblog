<?php
# Plugin: LnBlogAd
# A simple advertising banner.  This just displays a little icon and link
# that tells people you're using LnBlog.
class LnBlogAd extends Plugin {

    function __construct($do_output=0) {
        $this->plugin_desc = _("Shameless link whoring.  Put a link to LnBlog on the page.");
        $this->plugin_version = "0.2.1";
        $this->use_footer = false;
        $this->addOption("use_footer",
            _("Put the advertising in the footer, not the sidebar"),
            false, "checkbox");

        $this->addNoEventOption();

        parent::__construct();

        $location = $this->use_footer ? "footer" : "sidebar";
        $function = $this->use_footer ? "footer_output" : "output";
        $this->registerNoEventOutputHandler($location, $function);

        if ($do_output && $this->use_footer) {
            $this->footer_output();
        } elseif ($do_output) {
            $this->output();
        }
    }

    function output() { ?>
<div class="panel powered-by">
<a href="<?php echo PACKAGE_URL; ?>"><img alt="<?php pf_("Powered by %s", PACKAGE_NAME); ?>" title="<?php pf_("Powered by %s", PACKAGE_NAME); ?>" src="<?php echo getlink("logo.png", LINK_IMAGE); ?>" /></a>
</div>
<?php
    }

    function footer_output() {
        $blg = NewBlog();
        if ($blg->isBlog()) {
            $message = spf_("%1\s is powered by %2\s", $blg->name,
                            '<a href="'.PACKAGE_URL.'">'.PACKAGE_NAME.'</a>');
        } else {
            $message = spf_("Powered by %2\s",
                            '<a href="'.PACKAGE_URL.'">'.PACKAGE_NAME.'</a>');
        }
        echo $message;
    }
}

if (! PluginManager::instance()->plugin_config->value('lnblogad', 'creator_output', 0)) {
    $plug = new LnBlogAd();
}
