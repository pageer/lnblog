<?php  
class TagList extends Plugin {

	function TagList($do_output=0) {
		global $SYSTEM;

		$this->plugin_desc = _("Provides a list of links to view post for each tag used in the blog.");
		$this->plugin_version = "0.2.1";
		$this->addOption("header", _("Sidebar section heading"), _("Topics"));
		$this->addOption("show_feeds", _("Show RSS feeds"), true, 'checkbox');

		$this->addOption('no_event',
			_('No event handlers - do output when plugin is created'),
			$SYSTEM->sys_ini->value("plugins","EventDefaultOff", 0), 
			'checkbox');

		$this->getConfig();

		if ( $this->no_event || 
		     $SYSTEM->sys_ini->value("plugins","EventForceOff", 0) ) {
			# If either of these is true, then don't set the event handler
			# and rely on explicit invocation for output.
		} else {
			$this->registerEventHandler("sidebar", "OnOutput", "output");
		}
		
		if ($do_output) $this->output();
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
			?><li><a href="<?php echo $blg->uri('tags', 'tag='.$tag);?>"><?php echo $tag;?></a><?php
		
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
					echo ' <a href="'.$rdf_uri.'"><img src="'.
						getlink('rdf_feed.png').'" alt="'._("RSS 1.0 feed").
						'" title="'._("RSS 1.0 feed").'" /></a>';
				} elseif (! $rdf_uri && $xml_uri) { 
					echo ' <a href="'.$xml_uri.'"><img src="'.
						getlink('xml_feed.png').'" alt="'._("RSS 2.0 feed").
						'" title="'._("RSS 2.0 feed").'" /></a>';
				} elseif ($rdf_uri && $xml_uri) { 
					echo ' <a href="'.$rdf_uri.'"><img src="'.
						getlink('rdf_feed.png').'" alt="'._("RSS 1.0 feed").
						'" title="'._("RSS 1.0 feed").'" /></a> '.
						' <a href="'.$xml_uri.'"><img src="'.
						getlink('xml_feed.png').'" alt="'._("RSS 2.0 feed").
						'" title="'._("RSS 2.0 feed").'" /></a>';
				}
			}
?></li><?php
		} ?>
</ul><?php
	}

}

global $PLUGIN_MANAGER;
if (! $PLUGIN_MANAGER->plugin_config->value('taglist', 'creator_output', 0)) {
	$plug =& new TagList();
}
?>
