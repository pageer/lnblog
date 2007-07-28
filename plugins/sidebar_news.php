<?php
class News extends Plugin {

	function News($do_output=0) {
		global $SYSTEM;
		$this->plugin_desc = _("List the RSS feeds for the current page.");
		$this->plugin_version = "0.3.0";
		$this->addOption("header", 
			_("Sidebar section heading"), 
			_("News Feeds"));
		$this->addOption("std_icons", _("Use standard RSS icons for feeds"),
			true, "checkbox");
		$this->addOption("feed_url",
		                 _("External URL for main feed (e.g. through FeedBurner)"),
		                 '', 'text');
		$this->addOption("feed_link_text", 
		                 _("External feed link text"), 
		                 'Subscribe to RSS feed', 'text');
		$this->addOption("extra_markup",
		                 _("Extra HTML markup (e.g. for FeedBurner widgets)"), 
		                 '', 'textarea');

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
		$this->registerEventHandler("page", "OnOutput", "linkFeeds");
		
		if ($do_output) $this->output();
	}

	function output() {
		global $PLUGIN_MANAGER;
		
		$blg = NewBlog();
		if (! $blg->isBlog() ) return false;
		$ent = NewBlogEntry();
	
		$rss1_file = $PLUGIN_MANAGER->plugin_config->value(
			"rss1feedgenerator", "feed_file", "news.rdf");	
		$rss2_file = $PLUGIN_MANAGER->plugin_config->value(
			"rss2feedgenerator", "feed_file", "news.xml");
		$blog_feeds = BLOG_ROOT.PATH_DELIM.BLOG_FEED_PATH.PATH_DELIM;
		$blog_feeds_url = $blg->uri('base').BLOG_FEED_PATH."/";
	
		$rss1_comments = $PLUGIN_MANAGER->plugin_config->value(
			"rss1feedgenerator", "comment_file", "comments.rdf");
		$rss2_comments = $PLUGIN_MANAGER->plugin_config->value(
			"rss2feedgenerator", "comment_file", "comments.xml");
		$entry_feeds = $ent->isEntry() ? 
		               mkpath($ent->localpath(),ENTRY_COMMENT_DIR) : 
		               '';
		$entry_feeds_url = $ent->commentlink();
		
		$feed_links = array();
	
		$tpl = NewTemplate("sidebar_panel_tpl.php");
		if ($this->header) $tpl->set('PANEL_TITLE', $this->header);
		if (! $this->std_icons) $tpl->set('PANEL_CLASS', 'imglist');
		
		$feeds = array();
		# Elements are path, url, RSS version, and "is comment feed".
		if (! $this->feed_url) {
			$feeds[] = array(mkpath($blog_feeds,$rss2_file), 
			                        $blog_feeds_url.$rss2_file, 2, false);
			$feeds[] = array(mkpath($blog_feeds,$rss1_file), 
			                        $blog_feeds_url.$rss1_file, 1, false);
		} else {
			$feeds[] = array(false, $this->feed_url, 0, false);
		}
		$feeds[] = array(mkpath($entry_feeds,$rss2_comments), 
		                        $entry_feeds_url.$rss2_comments, 2, true);
		$feeds[] = array(mkpath($entry_feeds,$rss1_comments), 
		                        $entry_feeds_url.$rss2_comments, 1, true);
		
		foreach ($feeds as $feed) {
		
			if ($feed[0] === false) {
				if ($this->extra_markup) {
					$feed_links[] = $this->extra_markup;
				} else {
					$text = $this->feed_link_text;
					$title = _("Subscribe to updates with an RSS reader");
					$icon = $this->get_link_icon(2, false);
					$feed_links[] = $this->link_markup($this->feed_url, 'rss', $icon, $title, $text);
				}
			} elseif (file_exists($feed[0])) {
				$title = $this->get_link_title($feed[2], $feed[3]);
				$text = $this->get_link_text($feed[2], $feed[3]);
				$icon = $this->get_link_icon($feed[2], $feed[3]);
				$type = $feed[2] == 1 ? 'rdf' : 'rss';
				$feed_links[] = $this->link_markup($feed[1], $type, $icon, $title, $text);
			}
		}
		
		$tpl->set('PANEL_LIST', $feed_links);
		echo $tpl->process();
		
	}  # End function
	
	function get_link_title($ver, $is_comment) {
		if ($ver == 1 && $is_comment) $title = _("RSS 1.0 comment feed - links only");
		elseif ($ver == 2 && $is_comment) $title = _("RSS 2.0 comment feed - full comments");
		elseif ($ver == 1 && ! $is_comment) $title = _("RSS 1.0 blog entry feed - links only");
		elseif ($ver == 2 && ! $is_comment) $title = _("RSS 2.0 blog entry feed - full text");
		return $title;
	}
	
	function get_link_text($ver, $is_comment) {
		if ($this->std_icons) {
			if ($ver == 1 && $is_comment) $text = _("Comment headlines");
			elseif ($ver == 2 && $is_comment) $text = _("Comment text");
			elseif ($ver == 1 && ! $is_comment) $text = _("Entry headlines");
			elseif ($ver == 2 && ! $is_comment) $text = _("Entry text");
		} else {
			$text = '';
		}
		return $text;
	}
	
	function get_link_icon($ver, $is_comment) {
		if ($this->std_icons) {
			if ($ver == 1) $icon = getlink("rdf_feed.png", LINK_IMAGE);
			elseif ($ver == 2) $icon = getlink("xml_feed.png", LINK_IMAGE);
		} else {
			if ($ver == 1 && ! $is_comment) $icon = getlink("rss1_button.png", LINK_IMAGE);
			elseif ($ver == 2 && ! $is_comment) $icon = getlink("rss2_button.png", LINK_IMAGE);
			elseif ($ver == 1 && $is_comment) $icon = getlink("rss1_comments_button.png", LINK_IMAGE);
			elseif ($ver == 2 && $is_comment) $icon = getlink("rss2_comments_button.png", LINK_IMAGE);
		}
		return $icon;
	}
	
	function link_markup($href, $feed_type, $img, $title, $text=false) {
		switch($feed_type) {
			case 'xml':
			case 'rss':
				$type = 'applicaiton/rss+xml'; break;
			case 'rdf':
				$type = 'applicaiton/xml'; break;
			case 'atom':
				$type = 'applicaiton/atom+xml'; break;
		}
		$link = '<a href="'.$href.'" type="'.$type.'">'.
		        ($text ? $text." " : '').
		        '<img src="'.$img.'" alt="'.$title.'" title="'.$title.'" /></a>';
		return $link;
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
			$base_path = mkpath($param->display_object->localpath(),ENTRY_COMMENT_DIR);
			$rss2_file = mkpath($base_path, $rss2_comments);
			$rss1_file = mkpath($base_path, $rss1_comments);
			
			if (file_exists($rss2_comments) ) {
				$param->addRSSFeed($param->display_object->uri('base').
				                   ENTRY_COMMENT_DIR."/".$rss2_comments, 
				                   "application/rss+xml", _("Comments - RSS 2.0"));
			}

			# RSS 1 comments
			if (file_exists($rss1_comments) ) {
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
			
			if (! $this->feed_url) {
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
			} else {
				$param->addRSSFeed($this->feed_url, "application/rss+xml", _("RSS feed"));
			}
		}
	}


} 

global $PLUGIN_MANAGER;
if (! $PLUGIN_MANAGER->plugin_config->value('news', 'creator_output', 0)) {
	$newsfeeds =& new News();
}
?>
