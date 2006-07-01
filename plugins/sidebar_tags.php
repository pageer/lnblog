<?php  
class TagList extends Plugin {

	function TagList() {
		$this->plugin_desc = _("Provides a list of links to view post for each tag used in the blog.");
		$this->plugin_version = "0.2.0";
		$this->addOption("header", _("Sidebar section heading"), _("Topics"));
		$this->addOption("show_feeds", _("Show RSS feeds"), true, 'checkbox');
		$this->getConfig();
	}

	function output($parm=false) {

		global $PLUGIN_MANAGER;
		$parser =& $PLUGIN_MANAGER->plugin_config;
		
		$blg = NewBlog();
		if (! $blg->isBlog() ) return false;
		if (empty($blg->tag_list)) return false;

		$base_feed_path = $blg->home_path.PATH_DELIM.BLOG_FEED_PATH;
		$base_feed_uri = $blg->uri('base').BLOG_FEED_PATH.'/';

		if ($this->header) {
?><h3><?php echo $this->header;?></h3><?php } ?>
<ul><?php
		foreach ($blg->tag_list as $tag) { 
			?><li><a href="<?php echo $blg->getURL(); ?>tags.php?tag=<?php echo $tag;?>"><?php echo $tag;?></a><?php
		
			if ($this->show_feeds) {
				$topic = preg_replace('/\W/','',$tag);

				$rdf_file = $topic.'_'.
					$parser->value("RSS1FeedGenerator", "feed_file", "news.rdf");
				$xml_file = $topic.'_'.
					$parser->value("RSS2FeedGenerator", "feed_file", "news.xml");
				$rdf_uri = false;
				$xml_uri = false;

				if (file_exists($base_feed_path.PATH_DELIM.$rdf_file)) 
					$rdf_uri = $base_feed_uri.$rdf_file;
				if (file_exists($base_feed_path.PATH_DELIM.$xml_file)) 
					$xml_uri = $base_feed_uri.$xml_file;

				if ($rdf_uri && ! $xml_uri) { 
					echo ' (<a href="'.$rdf_uri.'">'._("RSS 1").'</a>)';
				} elseif (! $rdf_uri && $xml_uri) { 
					echo ' (<a href="'.$xml_uri.'">'._("RSS 2").'</a>)';
				} elseif ($rdf_uri && $xml_uri) { 
					echo ' (<a href="'.$rdf_uri.'">'._("RSS 1").'</a> | '.
					     '<a href="'.$xml_uri.'">'._("RSS 2").'</a>)'; 
				}
			}
?></li><?php
		} ?>
</ul><?php
	}

}

$tgl = new TagList();
$tgl->registerEventHandler("sidebar", "OnOutput", "output");
?>
