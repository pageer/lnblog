<?php
class News extends Plugin {

	function News($do_output=0) {
		global $SYSTEM;
		$this->plugin_desc = _("List the RSS feeds for the current page.");
		$this->plugin_version = "0.2.1";
		$this->addOption("header", 
			_("Sidebar section heading"), 
			_("News Feeds"));
		$this->addOption("std_icons", _("Use standard RSS icons for feeds"),
			true, "checkbox");

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

	function output() {
		global $PLUGIN_MANAGER;
		$rss1_file = $PLUGIN_MANAGER->plugin_config->value(
			"rss1feedgenerator", "feed_file", "news.rdf");
		$rss1_comments = $PLUGIN_MANAGER->plugin_config->value(
			"rss1feedgenerator", "comment_file", "comments.rdf");
		$rss2_file = $PLUGIN_MANAGER->plugin_config->value(
			"rss2feedgenerator", "feed_file", "news.xml");
		$rss2_comments = $PLUGIN_MANAGER->plugin_config->value(
			"rss2feedgenerator", "comment_file", "comments.xml");
	
		$blg = NewBlog();
		if (! $blg->isBlog() ) return false;
		
		$blog_feeds = BLOG_ROOT.PATH_DELIM.BLOG_FEED_PATH.PATH_DELIM;
		$blog_feeds_url = $blg->uri('base').BLOG_FEED_PATH."/";
		
		if ( file_exists($blog_feeds.$rss1_file) ||
		     file_exists($blog_feeds.$rss2_file)) {
			if ($this->header) { /* Suppress empty header. */ ?>
<h3><?php echo $this->header; ?></h3><?php 
			} 
			if ($this->std_icons) { ?>
<ul>
<?php
			} else { ?>
<ul class="imglist">
<?php
			}
			
			$ent = NewBlogEntry();
			$std_rss_icon = getlink("xml_feed.png", LINK_IMAGE);
			$std_rdf_icon = getlink("rdf_feed.png", LINK_IMAGE);
			$old_rss_icon = getlink("rss2_button.png", LINK_IMAGE);
			$old_rdf_icon = getlink("rss1_button.png", LINK_IMAGE);
			
			if ($ent->isEntry()) {
				$comm_feeds_url = $ent->commentlink();
				$ent_path = $ent->localpath().PATH_DELIM.
				            ENTRY_COMMENT_DIR.PATH_DELIM;
				
				$old_rss_comments_icon = getlink("rss2_comments_button.png", LINK_IMAGE);
				$old_rdf_comments_icon = getlink("rss1_comments_button.png", LINK_IMAGE);
				
				if (file_exists($ent_path.$rss1_comments) ) { 
					$title = _("RSS 1.0 comment feed - links only");
					if ($this->std_icons) {
						$text = _("Comment headlines");
						$icon = $std_rdf_icon;
					} else {
						$text = '';
						$icon = $old_rdf_comments_icon;
					}
					echo '<li>'.
					     $this->link_markup($comm_feeds_url.$rss1_comments,
					                        'rdf', $icon, $title,
					                        $text).
					     '</li>';
				}
				if (file_exists($ent_path.$rss2_comments) ) {
					
					$title = _("RSS 2.0 comment feed - full comments");
					if ($this->std_icons) {
						$text = _("Comment text");
						$icon = $std_rss_icon;
					} else {
						$text = '';
						$icon = $old_rss_comments_icon;
					}
					echo '<li>'.
					     $this->link_markup($comm_feeds_url.$rss2_comments,
					                        'rss', $icon, $title,
					                        $text).
					     '</li>';
				}
			}  # End inner if
			if (file_exists($blog_feeds.$rss1_file)) { 
				$title = _("RSS 1.0 blog entry feed - links only");
				if ($this->std_icons) {
					$text = _("Entry headlines");
					$icon = $std_rdf_icon;
				} else {
					$text = '';
					$icon = $old_rdf_icon;
				}
				echo '<li>'.
				     $this->link_markup($blog_feeds_url.$rss1_file,
				                        'rdf', $icon, $title,
				                        $text).
				     '</li>';
			}

			if (file_exists($blog_feeds.$rss2_file)) { 
				$title = _("RSS 2.0 blog entry feed - full text");
				if ($this->std_icons) {
					$text = _("Entry text");
					$icon = $std_rss_icon;
				} else {
					$text = '';
					$icon = $old_rss_icon;
				}
				echo '<li>'.
				     $this->link_markup($blog_feeds_url.$rss2_file,
				                        'rss', $icon, $title,
				                        $text).
				     '</li>';
			}
?>
</ul>
<?php 
		}  # End outer if
	}  # End function
	
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

} 

global $PLUGIN_MANAGER;
if (! $PLUGIN_MANAGER->plugin_config->value('news', 'creator_output', 0)) {
	$newsfeeds =& new News();
}
?>
