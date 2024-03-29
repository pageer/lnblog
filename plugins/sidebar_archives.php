<?php

# Plugin: Archives
# A sidebar plugin to display months of archives.
#
# This plugin provides quick, easy access to blog archives.  It produces a
# sidebar panel with a link for each month for which there are entries, up
# to a configurable maximum.

class Archives extends Plugin
{
    public $max_months;
    public $title;

    function __construct($do_output=0) {
        $this->plugin_desc = _("List the months of archives for a blog.");
        $this->plugin_version = "0.2.1";
        $this->addOption("max_months", _("Number of months to show"), 6, "number");
        $this->addOption(
            "title", _("Sidebar section title"),
            _("Archives"), "text"
        );
        $this->addNoEventOption();

        parent::__construct();

        $this->registerNoEventOutputHandler("sidebar", "output");

        if ($do_output) {
            $this->output();
        }
    }

    function output($parm=false) {
        $blg = NewBlog();
        if (! $blg->isBlog()) {
            return false;
        }

        $root = $blg->getURL();
        $month_list = $blg->getRecentMonthList($this->max_months);

        if ($this->title): ?>
<h3>
    <a href="<?php echo $root.BLOG_ENTRY_PATH ?>/"><?php echo $this->title ?></a>
</h3>
        <?php endif; ?>
<ul>
<?php
        foreach ($month_list as $month):
            $ts = mktime(0, 0, 0, $month["month"], 1, $month["year"]);
            $globals = new GlobalFunctions();
            $link_desc = $globals->constant('USE_STRFTIME') ?
                fmtdate("%B %Y", $ts) :
                fmtdate("F Y", $ts);
?>
<li><a href="<?php echo $month["link"] ?>"><?php echo $link_desc ?></a></li>
<?php
        endforeach;
?>
<li><a href="<?php echo $blg->uri('listall') ?>"><?php p_("Show all entries") ?></a></li>
</ul><?php
    }  # End of function
}

if (! PluginManager::instance()->plugin_config->value('archives', 'creator_output', 0)) {
    $rec = new Archives();
}

