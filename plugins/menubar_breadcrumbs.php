<?php
# Plugin: Breadcrumbs
# This plugin createa a breadcrumb trail in the page header.  It will display the
# path components to an entry or article, with links to each listing page.

class Breadcrumbs extends Plugin
{

    public $link_file;
    public $list_header;

    public function __construct($do_output=0) {

        $this->plugin_desc = _('Show a "bread-crumb" trail indicating the user\'s current location in the blog.');
        $this->plugin_version = "0.3.0";
        $this->addOption(
            "list_header", _("Heading at start of trail"),
            _("Location"), "text"
        );

        $this->addNoEventOption();

        parent::__construct();

        $this->registerNoEventOutputHandler("menubar", "output");

        if ($do_output) {
            $this->output();
        }
    }

    public function list_wrap($uri, $text) {
        return '<li><a href="'.$uri.'">'.$text."</a></li>\n";
    }

    public function output($parm=false) {
        $blog_registry = SystemConfig::instance()->blogRegistry();
        $blog = NewBlog();
        $ent = NewEntry();
        if (! $blog->isBlog() ) {
            return false;
        }

        $url = $blog_registry[$blog->blogid]->url();
        $urlpath = trim(parse_url($url, PHP_URL_PATH), '/');

        $ret = '';

        $path = trim($_SERVER['PHP_SELF'], '/');
        $path = str_replace($urlpath, '', $path);
        $path = trim($path, '/');

        $pieces = explode('/', $path);

        foreach ($pieces as $tok) {
            if ($tok == BLOG_ENTRY_PATH) {
                $ret .= $this->list_wrap($blog->uri('archives'), _("Archives"));
            } elseif ($tok == BLOG_ARTICLE_PATH) {
                $ret .= $this->list_wrap($blog->uri('articles'), _("Articles"));
            } elseif ($tok == BLOG_DRAFT_PATH) {
                $ret .= $this->list_wrap($blog->uri('drafts'), _("Drafts"));
            } elseif (is_numeric($tok) && strlen($tok) == 4) {
                $year = $tok;
                $ret .= $this->list_wrap($blog->uri('listyear', ['year' => $tok]), $tok);
            } elseif (is_numeric($tok) && strlen($tok) == 2 && isset($year)) {
                $month = fmtdate("%B", mktime(0, 0, 0, (int)$tok, 1, 2000));
                $ret .= $this->list_wrap($blog->uri('listmonth', ['year' => $year, 'month' => $tok]), $month);
            } elseif ($tok == ENTRY_PINGBACK_DIR) {
                $ret .= $this->list_wrap($ent->uri('pingback'), _("Pingbacks"));
            } elseif ($tok == ENTRY_TRACKBACK_DIR) {
                $ret .= $this->list_wrap($ent->uri('trackback'), _("TrackBacks"));
            } elseif ($tok == ENTRY_COMMENT_DIR) {
                $ret .= $this->list_wrap($ent->uri('comment'), _("Comments"));
            } elseif ($tok == 'index.php') {
                # Do nothing - we don't show the wrapper scripts.
            } elseif ($ent && $ent->isEntry()) {
                $ret .= $this->list_wrap($ent->uri('permalink'), $ent->subject);
            }

            $tok = strtok(DIRECTORY_SEPARATOR);
        }

        if (GET('action') == 'tags') {
            $ret .= $this->list_wrap($blog->uri('tags'), _('Tags'));
            if (GET('tag')) {
                $ret .= $this->list_wrap($blog->uri('tags', ['tag' =>htmlspecialchars(GET('tag'))]), htmlspecialchars(GET('tag')));
            }
        }

        if ($ret) {

            $ret = "<ul class=\"location\">\n".$this->list_wrap($blog->uri('blog'), $blog->name).$ret."</ul>\n";
            if ($this->list_header) {
                $ret = '<h2>'.$this->list_header."</h2>\n".$ret;
            }

            echo $ret;
        }
    }

}
$map = new Breadcrumbs();
