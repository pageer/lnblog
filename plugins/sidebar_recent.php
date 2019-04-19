<?php
# Plugin: Recent
# Puts a list of recent entries in the sidebar.
#
# This plugin has several options.  In addition to heading text and number of entry
# settings, there are options to configure the behavior when you're on the front page.
# Because the most recent entries will, by definition, be on the front page of a blog,
# the default behavior is to display older entries in that case, with a different entry
# header.  There is also an option to show a link to the front page.

class Recent extends Plugin {

    function __construct($do_output=0) {

        $this->plugin_desc = _("Show some of the more recent posts in the sidebar.");
        $this->plugin_version = "0.3.0";
        $this->addOption("old_header",
            _("Header for main blog page (entries not on main page)"),
            _("Older Entries"));
        $this->addOption("recent_header",
            _("Header for other pages (newest entries)"),
            _("Recent Entries"));
        $this->addOption("num_entries",
            _("Number of entries to show"), "Blog default", "text");
        $this->addOption("only_recent",
            _("Show the most recent entries on the front page (same links as entry pages)"),
              false, "checkbox");
        $this->addOption("show_main",
            _("Show link to main blog page"), true, "checkbox");
        $this->addOption("enable_cache",
            _("Enable plugin output caching"),
              true, "checkbox");
        $this->addNoEventOption();

        parent::__construct();

        $blg = NewBlog();
        if ($blg->isBlog()) {
            $this->cache_base_path = $blg->home_path;
        } else {
            $this->cache_base_path = false;
        }

        $this->registerNoEventOutputHandler("sidebar", "outputCache");
        $this->registerStandardInvalidators();

        if ($do_output) {
            $this->outputCache();
        }
    }

    function buildOutput($blg, $is_index=false) {

        if ( !($this->num_entries > 0) ) {
            $this->num_entries = false;
        }

        # Show some of the more recent entries.  If we're on the "front page"
        # of the blog, then show the next set of entries.  Otherwise, show the
        # most recent entries.
        if ($is_index) {
            $next_list = $blg->getNextMax($this->num_entries);
        } else {
            $next_list = $blg->getRecent($this->num_entries);
        }

        ob_start();

        if ( count($next_list) > 0 ) {
            if ($is_index) {
?>
<h3><a href="<?php echo $blg->getURL(); ?>"><?php echo htmlspecialchars($this->old_header); ?></a></h3>
<?php
            } else { # !$is_index
?>
<h3><a href="<?php echo $blg->getURL(); ?>"><?php echo htmlspecialchars($this->recent_header); ?></a></h3>
<?php
            } # End inner if
?>
<ul>
<?php
            foreach ($next_list as $ent) {
?>
<li><a href="<?php echo $ent->permalink(); ?>"><?php echo htmlspecialchars($ent->subject); ?></a></li>
<?php
            }    # End foreach
            if ($this->show_main) { /* Link to main page */ ?>
<li style="margin-top: 0.5em"><a href="<?php echo $blg->getURL();?>"><?php p_("Show home page");?></a></li><?php
            } ?>
</ul>
<?php
        }  # End outer if

        $content = ob_get_contents();
        ob_end_clean();
        return $content;

    } #End function

    function cachepath($new=false) {
        if ($this->cache_base_path) {
            if ($new) {
                return mkpath($this->cache_base_path, "cache", get_class($this)."_old_output.cache");
            } else {
                return mkpath($this->cache_base_path, "cache", get_class($this)."_new_output.cache");
            }
        } else {
            return false;
        }
    }

    function invalidateCache($obj=false) {

        $f = NewFS();

        $paths = array($this->cachepath(true), $this->cachepath(false));

        foreach ($paths as $cache_path) {
            if (file_exists($cache_path)) {
                $f->delete($cache_path);
            }
        }
    }

    function outputCache($obj=false, $suppress_login = true) {

        $b = is_a($obj, 'Blog') ? $obj : NewBlog();
        $f = NewFS();

        $is_index = ( current_url() == $b->uri('blog') ||
                      current_url() == $b->uri('blog')."index.php" );
        $is_index = $is_index && ! $this->only_recent;

        if (! $this->enable_cache) {
            echo $this->buildOutput($b, $is_index);
        } else {
            $cache_path = $this->cachepath($b);

            $vals = array(true, false);
            foreach ($vals as $v) {
                $cache_path = $this->cachepath($v);
                if ($cache_path && ! file_exists($cache_path)) {
                    $content = $this->buildOutput($b, $v);
                    if (! is_dir(dirname($cache_path))) {
                        $f->mkdir(dirname($cache_path));
                    }
                    $f->write_file($cache_path, $content);
                }
            }

            if (file_exists($this->cachepath($is_index))) {
                readfile($this->cachepath($is_index));
            }
        }
    }

}

if (! PluginManager::instance()->plugin_config->value('recent', 'creator_output', 0)) {
    $rec = new Recent();
}
