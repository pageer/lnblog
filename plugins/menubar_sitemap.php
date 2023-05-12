<?php

# Plugin: SiteMap
# Shows a user-defined site map in the menu bar.
#
# This plugin allows you to put a simple site map in the menubar of your
# blogs.  The site map itself is stored in a file that you must edit by hand.
#
# Note that there are two formats for the site map.  The default format is
# simply a list of hyperlinks with one link per line.  When the page is
# displayed, these will be converted to an HTML unordered list, with
# a customizable label in front of it.
#
# The second format, which is enabled by an option, is to treat the sitemap
# file as raw HTML to be included directly in the page.  This does not
# create an automatic list for you or, in fact, do any processing on the
# file at all.  This option is used if you want to do anything fancy with
# the menubar, such as adding drop-down menus with JavaScript or CSS.
#
# As of version 0.3.0 of this plugin (which ships with LnBlog 0.7.0), there is
# an auto-sitemap feature.  This is turned on by default and basically just
# puts links to all the registered blogs in the site map.  You can still use the
# old file-based sitemap as well, but it is no longer required.  If you use
# both, then the auto-generated links will be shown first and the custom ones
# will be second.

use LnBlog\Plugins\PluginTemplate;
use LnBlog\Storage\BlogRepository;

class SiteMap extends Plugin
{
    public const TEMPLATE = 'menubar_sitemap_tpl.php';
    private const PLUGIN_DIR = 'menubar_sitemap';

    public string $link_file;
    public bool $auto_map;
    public string $list_header;
    public bool $no_markup;

    private Blog $blog;
    private BlogRepository $blogRepository;
    private FS $fs;
    private SystemConfig $systemConfig;

    /**
     * @codeCoverageIgnore
     */
    public static function getLoader(): callable {
        return function (): Plugin {
            return new self();
        };
    }

    function __construct(
        Blog $blog = null,
        FS $fs = null,
        SystemConfig $systemConfig = null,
        BlogRepository $blogRepository = null
    ) {
        $this->blog = $blog ?: NewBlog();
        $this->fs = $fs ?: NewFS();
        $this->systemConfig = $systemConfig ?: SystemConfig::instance();
        $this->blogRepository = $blogRepository ?? new BlogRepository();

        $this->plugin_desc = _("Show a sitemap in the menubar");
        $this->plugin_version = "0.4.0";
        $this->list_header = _("Site Map");
        $this->no_markup = false;
        $this->addOption(
            "auto_map", _("Automatically list all blogs in sitemap"),
            true, "checkbox"
        );
        $this->addOption(
            "link_file", _("Name of link-list file"),
            "sitemap.htm", "text"
        );
        $this->addOption(
            "list_header", _("Heading at start of menubar"),
            _("Site Map"), "text"
        );
        $this->addOption(
            "no_markup",
            _("Use unmodified file contents as menubar (no auto-generated HTML)"),
            false, "checkbox"
        );

        $this->addNoEventOption();

        parent::__construct();

        $this->registerNoEventOutputHandler("menubar", "output");
        $this->registerEventHandler("blog", "OnInit", "updateManagedFiles");
        $this->registerEventHandler("loginops", "PluginOutput", "showLink");
    }

    public function updateManagedFiles($blog) {
        $blog->addManagedFile($this->link_file);
    }

    public function output($parm=false) {
        $map_file = $this->getMapFile();
        $template = new PluginTemplate(self::PLUGIN_DIR, self::TEMPLATE);

        $links = [];
        if ($this->auto_map) {
            $links = $this->getBlogList();
        }


        if ($this->no_markup && $map_file) {
            $data = $this->fs->read_file($map_file);
            $template->set('RAW_MARKUP', $this->fs->read_file($map_file));
        } else {
            #echo $markup;  # Output the auto-generated links, if any.
            if ($this->fs->is_file($map_file)) {
                $data = $this->fs->file($map_file);
                foreach ($data as $line) {
                    if (trim($line) != '') {
                        $links[] = $line;
                    }
                }
            } elseif (! $this->auto_map) {
                $links[] = '<a href="/" title="<?php p_("Site home page"); ?>"><?php p_("Home"); ?></a>';
            }

            $template->set('LINKS', $links);
            $template->set('HEADER', $this->list_header);
        }

        echo $template->process();
    }

    public function showLink() {
        $url = $this->blog->uri(
            'editfile',
            [
                'map' => 'yes',
                'file' => $this->link_file,
                'list' => 'yes',
                'richedit' => 'false'
            ]
        );
        $template = new PluginTemplate(self::PLUGIN_DIR, self::TEMPLATE);
        $template->set('SHOW_EDIT_LINK', true);
        $template->set('EDIT_URL', $url);
        $template->output();
    }

    private function getMapFile() {
        $map_file = '';
        $blog_file = Path::mk($this->blog->home_path, $this->link_file);
        $global_file = Path::mk($this->systemConfig->userData()->path(), $this->link_file);

        if ($this->blog->isBlog() && $this->fs->is_file($blog_file)) {
            $map_file = $blog_file;
        } elseif ($this->fs->is_file($global_file)) {
            $map_file = $global_file;
        }
        return $map_file;
    }

    private function getBlogList(): array {
        $list = $this->blogRepository->getAll();
        $ret = [];
        foreach ($list as $item) {
            $ret[] = [
                'name' => $item->name,
                'description' => $item->description,
                'url' => $item->getUrl(),
            ];
        }
        return $ret;
    }
}

// @codeCoverageIgnoreStart
return SiteMap::getLoader();
// @CodeCoverageIgnoreEnd
