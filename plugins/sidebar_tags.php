<?php
# Plugin: TagList
# This plugin will add a list of tags to your sidebar.  Each tag will be a link to
# a page showing all the entries filed under that tag.
#
# There are a couple of options for this plugin.  First, there are settings to control
# the sidebar header and the page header for the tag listing page.  There is also a
# setting to show links to RSS feeds for the tags, if they exist.  This will add an RSS icon with
# a link to the corresponding feed after each tag.

if (! class_exists("Blogroll")):  /* Start massive if statement */

class TagList extends Plugin {

	public function __construct($do_output=0) {
		$this->plugin_desc = _("Provides a list of links to view post for each tag used in the blog.");
		$this->plugin_version = "0.3.1";
		$this->addOption("header", _("Sidebar section heading"), _("Topics"));
		$this->addOption("page_title",
		                 _("Heading when viewing the full page"),
		                 _("Tags for this blog"));
		$this->addOption("show_feeds", _("Show RSS feeds"), true, 'checkbox');

        $this->addNoEventOption();

		parent::__construct();

        $this->registerNoEventOutputHandler("sidebar", "outputCache");
		$this->registerEventHandler("blogentry", "UpdateComplete", "invalidateCache");
		$this->registerEventHandler("blogentry", "InsertComplete", "invalidateCache");
		$this->registerEventHandler("article", "UpdateComplete", "invalidateCache");
		$this->registerEventHandler("article", "InsertComplete", "invalidateCache");
		$this->registerEventHandler("blog", "UpdateComplete", "invalidateCache");
		
		if ($do_output) $this->output();
	}

	function buildList() {
		$parser = PluginManager::instance()->plugin_config;
		
		$blg = NewBlog();
		if (! $blg->isBlog() ) {
			return false;
		}
		if (empty($blg->tag_list)) {
			return false;
		}

        $show_rss1 = PluginManager::instance()->pluginLoaded('RSS1FeedGenerator');
        $show_rss2 = PluginManager::instance()->pluginLoaded('RSS2FeedGenerator');

		$base_feed_path = $blg->home_path.PATH_DELIM.BLOG_FEED_PATH;
		$base_feed_uri = $blg->uri('base').BLOG_FEED_PATH.'/';
		
		$links = array();
		foreach ($blg->tag_list as $tag) {
			if (! $tag) {
				continue;
			}
			$l = '<a href="'.$blg->uri('tags', urlencode($tag)).'">'.
			     htmlspecialchars($tag).'</a>';
			if ($this->show_feeds) {
				$topic = preg_replace('/\W/','',$tag);

				$rdf_file = $topic.'_'.
					$parser->value("RSS1FeedGenerator", "feed_file", "news.rdf");
				$xml_file = $topic.'_'.
					$parser->value("RSS2FeedGenerator", "cat_suffix", "news.xml");
				
				$rdf_uri = false;
				$xml_uri = false;

				if ($show_rss1 && file_exists($base_feed_path.PATH_DELIM.$rdf_file)) {
					$rdf_uri = $base_feed_uri.$rdf_file;
				}
				if ($show_rss2 && file_exists($base_feed_path.PATH_DELIM.$xml_file)) {
					$xml_uri = $base_feed_uri.$xml_file;
				}

				if ($rdf_uri) { 
					$l .= ' <a href="'.$rdf_uri.'"><img src="'.
						getlink('rdf_feed.png').'" alt="'._("RSS 1.0 feed").
						'" title="'._("RSS 1.0 feed").'" /></a> ';
				}
				if ($xml_uri) { 
					$l .= ' <a href="'.$xml_uri.'"><img src="'.
						getlink('xml_feed.png').'" alt="'._("RSS 2.0 feed").
						'" title="'._("RSS 2.0 feed").'" /></a>';
				}
			}
			$links[] = $l;
		}
		return $links;
	}

	function buildOutput($parm=false) {

		$blg = NewBlog();
		if (! $blg->isBlog() || empty($blg->tag_list)) {
			return false;
		}

		$base_feed_path = $blg->home_path.PATH_DELIM.BLOG_FEED_PATH;
		$base_feed_uri = $blg->uri('base').BLOG_FEED_PATH.'/';

		$list = $this->buildList();
		
		if (! $list) {
			return '';
		}

		$tpl = NewTemplate("sidebar_panel_tpl.php");
		if ($this->header) {
			$tpl->set("PANEL_TITLE", $this->header);
			if ($blg->isBlog()) {
				$tpl->set("TITLE_LINK", $blg->uri('tags'));
			}
		}
		
		$tpl->set("PANEL_ID", 'tagpanel');
		$tpl->set("PANEL_LIST", $list);
		
		return $tpl->process();
	}
	
	function output_page() {
	
		$blog = NewBlog();
		Page::instance()->setDisplayObject($blog);

		$list = $this->buildList();
		
		Page::instance()->addInlineStylesheet(".taglist img { border: 0 }");
		
		$tpl = NewTemplate("list_tpl.php");
		$tpl->set("LIST_TITLE", $this->page_title);
		$tpl->set("LIST_CLASS", "taglist");
		$tpl->set("ITEM_LIST", $list);

		Page::instance()->title = spf_("Blogroll - ", $blog->name);
		Page::instance()->display($tpl->process(), $blog);
	
	}

}

endif; /* End massive if statement */

if (defined("PLUGIN_DO_OUTPUT")) {
	$plug = new TagList();
	$plug->output_page();
} else {
	if (! PluginManager::instance()->plugin_config->value('taglist', 'creator_output', 0)) {
		$plug = new TagList();
	}
}
?>
