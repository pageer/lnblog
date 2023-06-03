<?php

use LnBlog\Plugins\PluginTemplate;

# Plugin: SidebarSearch
# This plugin adds a configurable search feature to blogs.
#
# There are several options available for this plugin.  First, there are the
# display options.  This includes options to control the text and labels used
# to display the search box.  There is also a setting to control where the
# search box is displayed: the sidebar, the banner (where the blog title is),
# or the menubar (where the sitemap is).
#
# In addition, there is an option to set the search engine.  There are currently three choices.
#
# - Built-in search (default)
# - Google
# - Bing
#
# The Google and Bing searches, as you might expect, will simply do a site-specific
# search at those search engines using your blog's URL.  Whether or not that search
# actually returns anything will depend on when Google/Bing last indexed your site.
# Obviously, if they haven't crawled your site since a post went live, that post won't
# show in the search results.
#
# The built-in search, on the other hand, is guaranteed to always have access to all
# your posts, because it is built right into the plugin and can access the data files
# on your server directly.  The down side is that the search is pretty dumb (it does
# naive sting matching) and not particularly fast.  For the typical blog,
# if you don't get many search requests then the search performance will probably
# be "good enough".  However, if you start getting lots of traffic, you'll probably
# want to switch to Google or Bing.
class SidebarSearch extends Plugin
{
    public const TEMPLATE = 'search_form_tpl.php';
    private const PLUGIN_DIR = 'sidebar_search';

    public $caption;
    public $label;
    public $label_in_box;
    public $search_provider;
    public $show_in;

    private $blog;

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

        $this->plugin_desc = _("Search for terms in blog entries.");
        $this->plugin_version = "0.3.0";
        $this->addOption("caption", _("Caption for search panel"), _("Search"));
        $this->addOption("label", _("Search field label"), _('Search this weblog'));
        $this->addOption("label_in_box", _("Put label inside box (HTML5 only)"), false, "checkbox");
        $this->addOption(
            "search_provider", _("Search engine"), "native", "select",
            array(
                            'native' => 'Built-in search',
                            'google' => 'Google',
                            'bing' => 'Bing',
            )
        );
        $this->addOption(
            "show_in",
            _("Show search box in what part of page"),
            "sidebar", "select",
            array("sidebar"=>_("Sidebar"),
                               "banner"=>_("Banner"),
                               "menubar"=>_("Menubar"))
        );

        $this->addNoEventOption();

        parent::__construct();

        if ( ! ($this->no_event ||
                System::instance()->sys_ini->value("plugins", "EventForceOff", 0)) ) {
            switch ($this->show_in) {
                case "banner":
                    $this->registerEventHandler("banner", "OnOutput", "sidebar_panel");
                    break;
                case "menubar":
                    $this->registerEventHandler("menubar", "OnOutput", "sidebar_panel");
                    break;
                default:
                    $this->registerEventHandler("sidebar", "OnOutput", "sidebar_panel");
            }
        }
    }

    public function sidebar_panel($param=false) {
        $blg = $this->blog;
        if (! $blg->isBlog()) return true;

        $tooltip = _("Search for posts containing a space-separated list of words. If the search sting is enclosed in forward slashes, it will be treated as a regular expression.");

        switch ($this->show_in) {
            case "banner": $class = "bannerpanel"; 
                break;
            case "menubar": $class = "menupanel"; 
                break;
            default: $class = "panel";
        }

        $placeholder = $this->label_in_box ? ('placeholder="' . htmlspecialchars($this->label).  '"') : '';

        switch ($this->search_provider) {
            case 'google':
                $form_url = "https://www.google.com/search";
                $method = "get";
                $search_boxes = array(
                    'visible' => 'q',
                    'hidden' => array('as_sitesearch' => $blg->getURL()),
                );
                break;
            case 'bing':
                $form_url = "https://www.bing.com/search";
                $method = "get";
                $search_boxes = array(
                    'visible' => 'q',
                    'hidden' => array('q1' => 'site:'.$blg->getURL()),
                );
                break;
            case 'native':
            default:
                $form_url = $blg->getURL().'?plugin=sidebar_search&amp;show=sb_search_results';
                $method = "get";
                $search_boxes = array(
                    'visible' => 'sb_search_terms',
                    'hidden' => array(
                        #'blog' => $blg->blogid,
                        'plugin' => 'sidebar_search',
                        'show' => 'sb_search_results',
                    ),
                );
                break;
        }

        $template = new PHPTemplate('sidebar_panel_tpl.php');
        $template->set('PANEL_CLASS', $class);

        $form = new PluginTemplate(self::PLUGIN_DIR, self::TEMPLATE);
        $form->set('METHOD', $method);
        $form->set('FORM_URL', $form_url);
        $form->set('LABEL', $this->label);
        $form->set('LABEL_IN_BOX', $this->label_in_box);
        $form->set('SEARCH_BOXES', $search_boxes);

        if ($this->caption && $this->show_in == 'sidebar') {
            $template->set('PANEL_TITLE', $this->caption);
        }

        $template->set('PANEL_CONTENT', $form->process());
        $template->output();
    }

    private function findEntries(string $search_terms) {
        # Start by parsing out the search terms.  We use a painfully simple
        # syntax where search terms are separated by spaces.  There are no
        # logical connectives and only pages that match all terms are found.
        # Alternatively, if the search term is enclosed in slashes, then the
        # search term is treated as a raw regular expression.
        $ret = [];
        $search_data = trim($search_terms);
        if (preg_match("/\/.+\//", $search_data)) {
            $terms = [$search_data];
        } else {
            $temp_terms = explode(" ", $search_data);
            $terms = [];
            foreach ($temp_terms as $term) {
                if (trim($term) != "") {
                    $terms[] = "/" . trim($term) . "/i";
                }
            }
        }

        if (!$terms) {
            return $ret;
        }

        $entry_list = $this->blog->getEntries(-1);
        foreach ($entry_list as $ent) {
            $res = 0;
            foreach ($terms as $trm) {
                if (preg_match($trm, $ent->data) || preg_match($trm, $ent->subject)) {
                    $res++;
                } else {
                    break;
                }
            }
            if ($res == count($terms)) {
                $subject = $ent->subject ?: $ent->prettyDate($ent->post_ts);
                $ret[] = [
                    "link"=>$ent->permalink(),
                    "title"=>$subject
                ];
            }
        }

        return $ret;
    }

    public function outputPage(BasePages $web_page, string $action = ''): void {
        $blog = $this->blog;
        $web_page->getPage()->setDisplayObject($blog);
        $search_terms = trim(strip_tags(GET('sb_search_terms')));

        $link_list = [];
        if ($search_terms) {
            $link_list = $this->findEntries($search_terms);
        }

        $tpl = NewTemplate(LIST_TEMPLATE);
        
        if (!$search_terms) {
            $tpl->set("LIST_TITLE", spf_('No search terms'));
            $tpl->set("LINK_LIST", $link_list);
        } elseif (! $link_list) {
            $tpl->set("LIST_TITLE", _("No search results found."));
            $tpl->set("ITEM_LIST", array(_("No posts matched")));
        } elseif ($search_terms) {
            $tpl->set(
                "LIST_TITLE",
                spf_('Search results for "%s"', $search_terms)
            );
            $tpl->set("LINK_LIST", $link_list);
        }

        $body = $tpl->process();

        $web_page->getPage()->title = spf_("Search results -  %s", $blog->name);
        $web_page->getPage()->display($body, $blog);
    }
}

return SidebarSearch::getLoader();
