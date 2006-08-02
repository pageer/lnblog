<?php
class RSSLinks extends Plugin {

	function RSSLinks() {
		$this->plugin_desc = _("Add HTML link elements to relevant RSS feeds.");
		$this->plugin_version = "0.1.0";
	}
	
	function linkFeeds(&$param) {
		global $PLUGIN_MANAGER;
		global $PAGE;
		$param = $PAGE;
		$rss1_file = $PLUGIN_MANAGER->plugin_config->value(
			"rss1feedgenerator", "feed_file", "news.rdf");
		$rss1_comments = $PLUGIN_MANAGER->plugin_config->value(
			"rss1feedgenerator", "comment_file", "comments.rdf");
		$rss2_file = $PLUGIN_MANAGER->plugin_config->value(
			"rss2feedgenerator", "feed_file", "news.xml");
		$rss2_comments = $PLUGIN_MANAGER->plugin_config->value(
			"rss2feedgenerator", "comment_file", "comments.xml");

		$obj_type = strtolower(get_class($param->display_object));
		if ($obj_type == 'blogentry' || $obj_type == 'article') {
			# RSS 2 comments
			if (file_exists($param->display_object->localpath().PATH_DELIM.
			                ENTRY_COMMENT_DIR.PATH_DELIM.$rss2_comments) ) {
				$param->addRSSFeed($param->display_object->uri('base').
				                   ENTRY_COMMENT_DIR."/".$rss2_comments, 
				                   "application/rss+xml", _("Comments - RSS 2.0"));
			}

			# RSS 1 comments
			if (file_exists($param->display_object->localpath().PATH_DELIM.
			                ENTRY_COMMENT_DIR.PATH_DELIM.$rss1_comments) ) {
				$param->addRSSFeed($param->display_object->uri('base').
				                   ENTRY_COMMENT_DIR."/".$rss1_comments, 
				                   "application/xml", _("Comments - RSS 1.0"));
			}
		} 
		
		if ($obj_type == 'blog' || 
		          $obj_type == 'blogentry' || 
		          $obj_type == 'article') {
			
			if (is_a($param->display_object, 'Blog')) {
				$obj = $param->display_object;
			} else {
				$obj = $param->display_object->getParent();
			}
					  
			# RSS2 entries
			if (file_exists($obj->home_path.PATH_DELIM.
			                BLOG_FEED_PATH.PATH_DELIM.$rss2_file) ) {
				$param->addRSSFeed($obj->getURL().
				                   BLOG_FEED_PATH."/".$rss2_file, 
				                   "application/rss+xml", _("Entries - RSS 2.0"));
			}

			# RSS1 entries
			if (file_exists($obj->home_path.PATH_DELIM.
			                BLOG_FEED_PATH.PATH_DELIM.$rss1_file) ) {
				$param->addRSSFeed($obj->getURL().
				                   BLOG_FEED_PATH."/".$rss1_file, 
				                   "application/xml", _("Entries - RSS 1.0"));
			}
		}
	}

}

$lnk =& new RSSLinks();
$lnk->registerEventHandler("page", "OnOutput", "linkFeeds");
?>
