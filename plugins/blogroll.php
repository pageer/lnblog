<?php
# Plugin: Blogroll
# You can use this plugin to add a blogroll to your site.  It works as both a full-page
# list and a sidebar panel.
#
# This plugin basically just reads an OPML file and creates a list of links from it.
# Configuration options include the title for the sidebar panel as well as the heading
# for the full-page view.  And, of course, there's the OPML file path.
#
# Note that the OPML file upload is independent of this plugin.  You can use the
# "upload file" feature at the blog-level to upload the file and then point the
# plugin to that path.

if (! class_exists("Blogroll")):  # Start massive if statement to prevent multiple definition

class Blogroll extends Plugin
{
    public $file;
    public $caption;
    public $page_title;
    private $link_only;

    function __construct($do_output=false) {
        $this->plugin_version = "0.1.1";
        $this->plugin_desc = _("Creates a blogroll from an OPML file.");

        # Option: Blog roll file
        # This is the OPML-formatted file to use to create your blogroll.
        # For this setting, you must specify a local file path, not a URL.
        # The file path must be relative to the root of your blog (hence the
        # recommendation to use the upload feature).  So if you upload it to
        # the root directory of your blog, you can just specify the file name.
        # You will need to upload this file separately from this plugin.
        # It is recommended that you use the blog upload feature for this.
        $this->addOption("file", _("Blog roll file (in OPML format)"), '');

        # Option: Caption
        # This is the caption for the sidebar panel that will hold the blogroll.
        $this->addOption(
            "caption",
            _("Caption for blogroll sidebar panel"),
            _("Blogroll")
        );

        # Option: Heading when viewing page
        # This is the heading that is displayed at the top of the page when the
        # blog roll is viewed as a full page, rather than just a sidebar panel.
        $this->addOption(
            "page_title",
            _("Heading when viewing the full page"),
            _("Other blogs of interest")
        );

        # Option: No event handlers
        # Enable this to suppress the event handlers used for output.  This means that
        # you will need to edit your templates and instantiate the plugin where you want
        # its output to appear.
        $this->addNoEventOption();

        $this->link_only = true;

        parent::__construct();

        $this->registerNoEventOutputHandler("sidebar", "output");

        $this->registerEventHandler("page", "OnOutput", "add_stylesheet");

        if ($do_output) {
            $this->output();
        }
    }

    function get_file_path(&$blog) {
        $file = Path::mk($blog->home_path, $this->file);
        if ($this->file && file_exists($file)) return $file;
        else return false;
    }

    function output_opml() {

        $fs = NewFS();
        $b = NewBlog();
        $file = $this->get_file_path($b);

        if (! file_exists($file)) return false;

        $parser = new SimpleXMLReader($fs->read_file($file));
        $parser->parse();

        foreach ($parser->domtree['children'] as $child) {
            if ($child['tag'] == 'body') {
                $outlines = &$child['children'];
            }
        }
        if (! isset($outlines)) return false;
        $this->dump_outline($outlines);
    }

    function dump_outline($ol) {
        if (! isset($ol)) return false;

        echo "<ul>\n";
        foreach ($ol as $item) {
            echo "<li>\n";
            if (isset($item['attributes']['XMLURL'])) {
                $this->show_item($item);
            } else {
                echo htmlspecialchars($item['attributes']['TITLE'])."<br />\n";
                $this->dump_outline($item['children']);
            }
            echo "</li>\n";
        }

        echo "</ul>\n";

    }

    function show_item(&$item) {
        $title = htmlspecialchars($item['attributes']['TITLE']);
        $html_url = ($item['attributes']['HTMLURL']);
        $rss_url = ($item['attributes']['XMLURL']);
        $description = htmlspecialchars($item['attributes']['DESCRIPTION']);

    ?>
        <a href="<?php echo $html_url;?>" title="<?php echo $description;?>"><?php echo $title;?></a>
    <?php
        if (! $this->link_only) {
        ?>
        (<a href="<?php echo $rss_url;?>">RSS</a>)<br />
        <div class="description"><?php echo $description;?></div>

        <?php
        }
    }

    function output() {
        $blog = NewBlog();

        if (! file_exists($this->get_file_path($blog))) return false;

        $tpl = NewTemplate("sidebar_panel_tpl.php");
        if ($this->caption) {
            if ($blog->isBlog()) {
                $tpl->set(
                    'TITLE_LINK',
                    $blog->uri(
                        'plugin', [
                                     'plugin' => str_replace(".php", '', basename(__FILE__)),
                        'show'=>'yes']
                    )
                );
            }
            $tpl->set('PANEL_TITLE', $this->caption);
        }
        $tpl->set('PANEL_ID', 'blogroll');

        ob_start();
        $this->output_opml();
        $tpl->set('PANEL_CONTENT', ob_get_contents());
        ob_end_clean();
        echo $tpl->process();
    }

    function output_page() {
        $blog = NewBlog();
        Page::instance()->setDisplayObject($blog);

        ob_start();
        echo "<h3>".$this->page_title."</h3>\n";
        $this->link_only = false;
        $this->output_opml();
        $body = ob_get_contents();
        ob_end_clean();

        Page::instance()->title = spf_("Blogroll - ", $blog->name);
        Page::instance()->display($body, $blog);
    }

    function add_stylesheet() {
        ob_start();
?>
.description {
    font-size: 80%;
}

#blogroll ul li ul li {
    list-style-type: square;
    list-style-position: inside;
}

#blogroll ul {
    margin: 0;
}
<?php
        $data = ob_get_contents();
        ob_end_clean();
        Page::instance()->addInlineStylesheet($data);
    }

}

endif; /* End massive if statement */

if (defined("PLUGIN_DO_OUTPUT")) {
    $plug = new Blogroll();
    $plug->output_page();
} else {
    if (! PluginManager::instance()->plugin_config->value('blogroll', 'creator_output', 0)) {
        $plug = new Blogroll();
    }
}
