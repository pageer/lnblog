<?php
class RSSLinks extends Plugin {

	function RSSLinks() {
		$this->plugin_desc = _("Add HTML link elements to relevant RSS feeds.");
		$this->plugin_version = "0.1.0";
	}
	
	function linkFeeds(&$param) {
		$obj_type = strtolower(get_class($param->display_object));
		if ($obj_type == 'blogentry' || $obj_type == 'article') {
			if (file_exists($param->display_object->localpath().PATH_DELIM.
			                ENTRY_COMMENT_DIR.PATH_DELIM.COMMENT_RSS2_PATH) ) {
				$param->addRSSFeed($param->display_object->permalink().
				                   ENTRY_COMMENT_DIR."/".COMMENT_RSS2_PATH, 
				                   "application/xml+rss", _("Comments - RSS 2.0"));
			}
			if (file_exists($param->display_object->localpath().PATH_DELIM.
			                ENTRY_COMMENT_DIR.PATH_DELIM.COMMENT_RSS1_PATH) ) {
				$param->addRSSFeed($param->display_object->permalink().
				                   ENTRY_COMMENT_DIR."/".COMMENT_RSS1_PATH, 
				                   "application/xml", _("Comments - RSS 1.0"));
			}
		} elseif ($obj_type == 'blog' || 
		          $obj_type == 'blogentry' || 
		          $obj_type == 'article') {
			if (file_exists($param->display_object->home_path.PATH_DELIM.
			                BLOG_FEED_PATH.PATH_DELIM.BLOG_RSS2_NAME) ) {
				$param->addRSSFeed($param->display_object->getURL().
				                   BLOG_FEED_PATH."/".BLOG_RSS2_NAME, 
				                   "application/xml+rss", _("Entries - RSS 2.0"));
			}
			if (file_exists($param->display_object->home_path.PATH_DELIM.
			                BLOG_FEED_PATH.PATH_DELIM.BLOG_RSS1_NAME) ) {
				$param->addRSSFeed($param->display_object->getURL().
				                   BLOG_FEED_PATH."/".BLOG_RSS1_NAME, 
				                   "application/xml", _("Entries - RSS 1.0"));
			}
		}
	}

}

$lnk = new RSSLinks();
$lnk->registerEventHandler("page", "OnOutput", "linkFeeds");
?>
