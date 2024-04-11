<?php
# Plugin: AuthorBio
# This plugin lets you display biographical information about the author in the sidebar.
# It includes a configurable title and HTML text input as well as an option to display
# a picture.

namespace LnBlog\Plugins;

use LnBlog\Plugins\PluginTemplate;

class AuthorBio extends \Plugin
{
    const PLUGIN_DIR = 'author_bio';
    const TEMPLATE = 'panel_tpl.php';

    public $title;
    public $picture_url;
    public $bio;
    public $markup_type;

    /**
     * @codeCoverageIgnore
     */
    public static function getLoader(): callable {
        return function (): Plugin {
            return new self();
        };
    }

    public function __construct() {
        $this->plugin_version = '0.1.2';
        $this->plugin_desc = _('Display information about the author in the sidebar.');

        # Option: Sidebar box title
        # The title for the sidebar panel that contains the widget.
        $this->addOption('title', _('Sidebar box title'), _('About Me'));

        # Option: URL of your profile photo
        # This is the full URL of the profile picture you want to use.
        # Leave this as an empty string to not have a profile picture.
        # Note that this plugin does not handle uploading the picture,
        # so you'll have to do that separately.  For instance, you can upload the
        # picture using the "upload file" feature for the blog and
        # select the URL for that file.
        $this->addOption('picture_url', _('URL of your profile photo'), '');

        # Option: Bio to display
        # The bio text that will be shown in the widget.  The text format is
        # controlled by the <Markup format> option.
        $this->addOption('bio', _('Bio to display'), '', 'textarea');

        $options = TextProcessor::getFilterList();
        # Option: Markup format
        # This is the format of the text that is used for the bio.  It can be any
        # of the supported text filters, the defaults being <LBCode Markup>, auto-markup, and HTML.
        # The default is plain-old HTML.
        $this->addOption('markup_type', 'Markup format', MARKUP_HTML, 'select', $options);

        # Option: No event handlers
        # Enable this to suppress the event handlers used for output.  This means that
        # you will need to edit your templates and instantiate the plugin where you want
        # its output to appear.
        $this->addNoEventOption();

        parent::__construct();

        $this->registerNoEventOutputHandler("sidebar", "output");
    }

    public function output() {
        if (! $this->picture_url && ! $this->bio) {
            return;
        }

        $pluginTpl = new PluginTemplate(self::PLUGIN_DIR, self::TEMPLATE);
        $pluginTpl->set('BIO_PIC', $this->picture_url);
        $bio_text = TextProcessor::get($this->markup_type, null, $this->bio)->getHTML();
        $pluginTpl->set('BIO', $bio_text);

        $tpl = new PHPTemplate('sidebar_panel_tpl.php');
        $tpl->set('PANEL_TITLE', $this->title);
        $tpl->set('PANEL_CLASS', 'panel');
        $tpl->set('PANEL_CONTENT', $pluginTpl->process());

        $tpl->output();
    }
}

// @codeCoverageIgnoreStart
//return AuthorBio::getLoader();
// @codeCoverageIgnoreEnd
