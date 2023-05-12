<?php

# Plugin: PageHeader
# This plugin adds header text to the page banner.
#
# It can show the blog name as a heading link in the banner and optionally
# show the blog description as a lesser heading.

use LnBlog\Plugins\PluginTemplate;

class PageHeader extends Plugin
{
    private const PLUGIN_DIR = 'banner_pageheader';
    private const TEMPLATE = 'header_tpl.php';

    public $show_desc;

    private Blog $blog;

    /**
     * @codeCoverageIgnore
     */
    public static function getLoader(): callable {
        return function (): Plugin {
            return new self();
        };
    }

    public function __construct(Blog $blog = null) {
        $this->blog = $blog ?: NewBlog();

        $this->plugin_desc = _("Output a banner for the page.");
        $this->plugin_version = "0.2.4";

        # Option: Show description
        # If enabled, the blog description will be displayed beneath the title.
        $this->addOption("show_desc", _("Show description"), false, "checkbox");

        $this->addNoEventOption();

        parent::__construct();

        $this->registerNoEventOutputHandler("banner", "output");
    }

    public function output($parm=false) {
        if ($this->blog->isBlog()) {
            $name = $this->blog->name;
            $description = $this->blog->description;
            $url = $this->blog->uri('blog');
        } else {
            $name = PACKAGE_NAME;
            $description = PACKAGE_DESCRIPTION;
            $url = PACKAGE_URL;
        }
        $template = new PluginTemplate(self::PLUGIN_DIR, self::TEMPLATE);
        $template->set('SHOW_DESCRIPTION', $this->show_desc);
        $template->set('NAME', $name);
        $template->set('DESCRIPTION', $description);
        $template->set('URL', $url);

        $template->output();
    }
}

if (! PluginManager::instance()->plugin_config->value('pageheader', 'creator_output', 0)) {
    $plug = new PageHeader();
}
//
// @codeCoverageIgnoreStart
return PageHeader::getLoader();
// @codeCoverageIgnoreEnd
