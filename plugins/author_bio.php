<?php
# Plugin: AuthorBio
# This plugin lets you display biographical information about the author in the sidebar.
# It includes a configurable title and HTML text input as well as an option to display
# a picture.

class AuthorBio extends Plugin
{

    public $title;
    public $picture_url;
    public $bio;
    public $markup_type;

    public function __construct($do_output=false) {
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

        Page::instance()->addInlineStylesheet($this->styles());

        $this->registerNoEventOutputHandler("sidebar", "output");

        if ($do_output) {
            $this->output();
        }
    }

    public function styles() {
        ob_start();
        // Hack for IDE syntax highlighting
        if (0) {
            ?><style type="text/css"><?php 
        } ?>
        .bio_picture img {
            border: 1px solid black;
            box-shadow: 1px 1px 1px 1px rgba(0, 0, 0, .5);
            max-width: 100%;
            border-radius: 5px;
        }
        .bio_content {
            margin-top: 1em;
        }
        <?php if (0) { 
            ?></style><?php 
        }
        $ret = ob_get_clean();
        return $ret;
    }

    public function output() {
        if (! $this->picture_url && ! $this->bio) {
            return;
        }

        $tpl = NewTemplate('sidebar_panel_tpl.php');

        $tpl->set('PANEL_TITLE', $this->title);

        ob_start();
        ?>
        <?php if ($this->picture_url): ?>
        <div class="bio_picture">
            <img src="<?php echo $this->picture_url;?>" alt="" style="display: block; margin: 0 auto"/>
        </div>
        <?php endif; ?>
        <div class="bio_content">
            <?php echo TextProcessor::get($this->markup_type, null, $this->bio)->getHTML();?>
        </div>
        <?php
        $content = ob_get_clean();
        $tpl->set('PANEL_CLASS', 'panel');
        $tpl->set('PANEL_CONTENT', $content);
        echo $tpl->process();
    }
}

if (! PluginManager::instance()->plugin_config->value('author_bio', 'creator_output', 0)) {
    $plug = new AuthorBio();
}

