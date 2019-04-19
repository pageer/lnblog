<?php
# Plugin: Articles
# A sidebar panel that displays your static articles.  It includes settings
# for the panel title as well as to show a link to the article index page.
#
# This plugin also allows you to add a list of ad hoc links to it.  This is
# done by simply editing the specified file.  The format is plain HTML, with
# one link tag per line, same as the other "list of links" files.

class Articles extends Plugin {
    public function __construct($do_output=0) {
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

        $blg = NewBlog();
        $u = NewUser();
        if (! $blg->isBlog()) {
            return false;
        }

        $art_list = $blg->getArticleList();
        $tpl = NewTemplate("sidebar_panel_tpl.php");
        $items = array();

        if (count($art_list) === 0) {
            return '';
        }

        if ($this->header) {
            $tpl->set("PANEL_TITLE", ahref($blg->uri('articles'), $this->header));
        }

        foreach ($art_list as $art) {
            $items[] = ahref($art['link'], htmlspecialchars($art['title']));
        }

        if ( is_file(mkpath($blg->home_path, $this->custom_links)) ) {
            $data = file(mkpath($blg->home_path, $this->custom_links));
            foreach ($data as $line) {
                $items[] = $line;
            }
        }

        if ($this->static_link) {
            $items[] = [
                'description'=>ahref($blg->uri('articles'), $this->showall_text),
                'style'=>'margin-top: 0.5em'
            ];
        }

        if (System::instance()->canModify($blg, $u)) {
            $items[] = [
                'description' => ahref(
                    $blg->uri('editfile', $this->custom_links),
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

