<?php
# Plugin: Breadcrumbs
# This plugin createa a breadcrumb trail in the page header.  It will display the
# path components to an entry or article, with links to each listing page.

use LnBlog\Plugins\PluginTemplate;

class Breadcrumbs extends Plugin
{
    private const PLUGIN_DIR = 'menubar_breadcrumbs';
    private const TEMPLATE = 'breadcrumb_tpl.php';

    public $link_file;
    public $list_header;

    private Blog $blog;
    private BlogEntry $entry;

    /**
     * @codeCoverageIgnore
     */
    public static function getLoader(): callable {
        return function (): Plugin {
            return new self();
        };
    }

    public function __construct(Blog $blog = null, BlogEntry $entry = null) {
        $this->blog = $blog ?: NewBlog();
        $this->entry = $entry ?: NewEntry();

        $this->plugin_desc = _('Show a "bread-crumb" trail indicating the user\'s current location in the blog.');
        $this->plugin_version = "0.4.0";
        $this->addOption(
            "list_header",
            _("Heading at start of trail"),
            _("Location"),
            "text"
        );

        $this->addNoEventOption();

        parent::__construct();

        $this->registerNoEventOutputHandler("menubar", "output");
    }

    public function output($parm=false) {
        if (! $this->blog->isBlog() ) {
            return;
        }

        $url = $this->blog->uri('base');
        $urlpath = trim(parse_url($url, PHP_URL_PATH), '/');

        $ret = '';
        $items = [];

        $path = trim($_SERVER['PHP_SELF'], '/');
        $path = str_replace($urlpath, '', $path);
        $path = trim($path, '/');

        $pieces = explode('/', $path);

        foreach ($pieces as $tok) {
            if ($tok == BLOG_ENTRY_PATH) {
                $items[] = $this->packageItem($this->blog->uri('archives'), _("Archives"));
            } elseif ($tok == BLOG_ARTICLE_PATH) {
                $items[] = $this->packageItem($this->blog->uri('articles'), _("Articles"));
            } elseif ($tok == BLOG_DRAFT_PATH) {
                $items[] = $this->packageItem($this->blog->uri('drafts'), _("Drafts"));
            } elseif (is_numeric($tok) && strlen($tok) == 4) {
                $year = $tok;
                $items[] = $this->packageItem($this->blog->uri('listyear', ['year' => $tok]), $tok);
            } elseif (is_numeric($tok) && strlen($tok) == 2 && isset($year)) {
                $month = fmtdate("%B", mktime(0, 0, 0, (int)$tok, 1, 2000));
                $items[] = $this->packageItem($this->blog->uri('listmonth', ['year' => $year, 'month' => $tok]), $month);
            } elseif ($tok == ENTRY_PINGBACK_DIR) {
                $items[] = $this->packageItem($this->entry->uri('pingback'), _("Pingbacks"));
            } elseif ($tok == ENTRY_TRACKBACK_DIR) {
                $items[] = $this->packageItem($this->entry->uri('trackback'), _("TrackBacks"));
            } elseif ($tok == ENTRY_COMMENT_DIR) {
                $items[] = $this->packageItem($this->entry->uri('comment'), _("Comments"));
            } elseif ($tok == 'index.php') {
                # Do nothing - we don't show the wrapper scripts.
            } elseif ($this->entry && $this->entry->isEntry() && !$this->entry->isDraft()) {
                $items[] = $this->packageItem($this->entry->uri('permalink'), $this->entry->subject);
            } elseif ($this->entry && $this->entry->isEntry() && $this->entry->isDraft()) {
                $subject = $this->entry->subject ?: _('New entry');
                $items[] = $this->packageItem($this->entry->uri('base'), $subject);
            }

            $tok = strtok(DIRECTORY_SEPARATOR);
        }

        if (GET('action') == 'tags') {
            $items[] = $this->packageItem($this->blog->uri('tags'), _('Tags'));
            if (GET('tag')) {
                $items[] = $this->packageItem($this->blog->uri('tags', ['tag' =>htmlspecialchars(GET('tag'))]), htmlspecialchars(GET('tag')));
            }
        }

        $template = new PluginTemplate(self::PLUGIN_DIR, self::TEMPLATE);
        $template->set('HEADER', $this->list_header);
        $template->set('ITEMS', $items);
        $template->set('HOMEURL', $url);
        if ($items) {
            $template->output();
        }
    }

    private function packageItem(string $url, string $name): array {
        return [
            'title' => $name,
            'url' => $url,
        ];
    }
}

// @codeCoverageIgnoreStart
return Breadcrumbs::getLoader();
// @codeCoverageIgnoreEnd
