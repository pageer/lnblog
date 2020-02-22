<?php
# Plugin: Articles
# A sidebar panel that displays your static articles.  It includes settings
# for the panel title as well as to show a link to the article index page.
#
# This plugin also allows you to add a list of ad hoc links to it.  This is
# done by simply editing the specified file.  The format is plain HTML, with
# one link tag per line, same as the other "list of links" files.

class Articles extends Plugin {
    private $blog;
    private $user;
    private $fs;
    private $system;

    public function __construct(
        $do_output = 0,
        Blog $blog = null,
        User $user = null,
        FS $fs = null,
        System $system = null
    ) {
        $this->blog = $blog;
        $this->user = $user;
        $this->fs = $fs ?: NewFS();
        $this->system = $system ?: System::instance();

        $this->plugin_desc = _("List the articles for a blog.");
        $this->plugin_version = "0.2.5";
        $this->header = _("Articles");
        $this->static_link = true;
        $this->custom_links = "links.htm";
        $this->addOption(
            "header",
            _("Sidebar section heading"),
            _("Articles"),
            "text"
        );
        $this->addOption(
            "static_link",
            _("Show link to list of static articles"),
            true,
            "checkbox"
        );
        $this->addOption(
            "showall_text",
            _("Text for link to all static articles"),
            _("All static pages"),
            "text"
        );
        $this->addOption(
            "custom_links",
            _("File where additional links to display are stored"),
            "links.htm",
            "true"
        );

        $this->addNoEventOption();

        parent::__construct();

        $this->registerNoEventOutputHandler("sidebar", "outputCache");
        $this->registerStandardInvalidators();

        if ($do_output) {
            $this->buildOutput();
        }
    }

    function buildOutput($parm=false) {

        $blg = $this->blog ?: NewBlog();
        $u = $this->user ?: NewUser();
        if (! $blg->isBlog()) {
            return '';
        }

        $art_list = $blg->getArticleList();
        if (count($art_list) === 0) {
            return '';
        }

        $tpl = NewTemplate("sidebar_panel_tpl.php");
        $items = array();

        if ($this->header) {
            $tpl->set("PANEL_TITLE", ahref($blg->uri('articles'), $this->header));
        }

        foreach ($art_list as $art) {
            $items[] = ahref($art['link'], htmlspecialchars($art['title']));
        }

        $path = Path::mk($blg->home_path, $this->custom_links);
        if ($this->fs->is_file($path)) {
            $file_content = $this->fs->read_file($path);
            // Remove crap added by WYSIWYG HTML editors.
            $file_content = preg_replace('|(</[^>]+>)|', "$1\n", $file_content);
            $file_content = preg_replace('|&nbsp;|i', " ", $file_content);
            $file_content = strip_tags($file_content, '<A>');
            $data = explode("\n", $file_content);
            foreach ($data as $line) {
                $line = trim($line);
                if ($line) {
                    $items[] = $line;
                }
            }
        }

        if ($this->static_link) {
            $items[] = [
                'description'=>ahref($blg->uri('articles'), $this->showall_text),
                'style'=>'margin-top: 0.5em'
            ];
        }

        if ($this->system->canModify($blg, $u)) {
            $items[] = [
                'description' => ahref(
                    $blg->uri('editfile', ['file' => $this->custom_links]),
                    _("Add custom links")
                ),
                'style'=>'margin-top: 0.5em'
            ];
        }

        $tpl->set('PANEL_LIST', $items);
        return $tpl->process();
    }  # End function

}

if (! PluginManager::instance()->plugin_config->value('articles', 'creator_output', 0)) {
    $art = new Articles();
}

