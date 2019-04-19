<?php

# Plugin: PageHeader
# This plugin adds header text to the page banner.
#
# It can show the blog name as a heading link in the banner and optionally
# show the blog description as a lesser heading.

class PageHeader extends Plugin {

    public function __construct($do_output=0) {

        $this->plugin_desc = _("Output a banner for the page.");
        $this->plugin_version = "0.2.4";

        # Option: Show description
        # If enabled, the blog description will be displayed beneath the title.
        $this->addOption("show_desc", _("Show description"), false, "checkbox");

        $this->addNoEventOption();

        parent::__construct();

        $this->registerNoEventOutputHandler("banner", "output");

        if ($do_output) {
            $this->output();
        }
    }

    public function output($parm=false) {
        $blg = NewBlog();
        if ($blg->isBlog()): ?>
<h1><a href="<?php echo $blg->uri('blog')?>" title="<?php echo $blg->description?>"><?php echo $blg->name; ?></a></h1><?php
            if ($this->show_desc): ?>
<h3><?php echo $blg->description;?></h3><?php
            endif;
        else: ?>
<h1><a href="<?php echo PACKAGE_URL?>"><?php echo PACKAGE_NAME?></a></h1><?php
            if ($this->show_desc): ?>
<h3><?php echo PACKAGE_DESCRIPTION?></h3><?php
            endif;
        endif;
    }
}

if (! PluginManager::instance()->plugin_config->value('pageheader', 'creator_output', 0)) {
    $plug = new PageHeader();
}
