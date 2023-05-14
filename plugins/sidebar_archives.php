<?php

# Plugin: Archives
# A sidebar plugin to display months of archives.
#
# This plugin provides quick, easy access to blog archives.  It produces a
# sidebar panel with a link for each month for which there are entries, up
# to a configurable maximum.

class Archives extends Plugin
{
    public int $max_months;
    public string $title;
    public string $all_link;

    /**
     * @codeCoverageIgnore
     */
    public static function getLoader(): callable {
        return function (): Plugin {
            return new self();
        };
    }

    function __construct() {
        $this->plugin_desc = _("List the months of archives for a blog.");
        $this->plugin_version = "0.3.0";
        $this->addOption("max_months", _("Number of months to show"), 6, "number");
        $this->addOption(
            "title", _("Sidebar section title"),
            _("Archives"), "text"
        );
        $this->addOption(
            "all_link",
            _('Text for "show all" link'),
            _("Show allentries"),
            "text"
        );
        $this->addNoEventOption();

        parent::__construct();

        $this->registerNoEventOutputHandler("sidebar", "output");
    }

    function output($page) {
        if (!$page instanceof Page) {
            return false;
        }

        $blg = $page->getBlog();

        if ($blg === null || !$blg->isBlog()) {
            return false;
        }

        $month_list = $blg->getRecentMonthList($this->max_months);

        if (!$month_list) {
            return false;
        }

        $template = new PHPTemplate('sidebar_panel_tpl.php');

        if ($this->title) {
            $template->set('PANEL_TITLE', $this->title);
            $template->set('TITLE_LINK', $blg->uri('archives'));
        }

        $items = [];

        foreach ($month_list as $month) {
            $ts = mktime(0, 0, 0, $month["month"], 1, $month["year"]);
            $globals = new GlobalFunctions();
            $link_desc = $globals->constant('USE_STRFTIME') ?
                fmtdate("%B %Y", $ts) :
                fmtdate("F Y", $ts);
            $items[] = sprintf('<a href="%s">%s</a>', $month["link"], $link_desc);
        }

        if ($this->all_link) {
            $items[] = sprintf('<a href="%s">%s</a>', $blg->uri('listall'), $this->all_link);
        }

        $template->set('PANEL_LIST', $items);
        $template->output();
    }
}

return Archives::getLoader();
