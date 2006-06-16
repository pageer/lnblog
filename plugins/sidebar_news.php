<?php
class News extends Plugin {

	function News() {
		$this->plugin_desc = _("List the RSS feeds for the current page.");
		$this->plugin_version = "0.2.0";
		$this->addOption("header", 
			_("Sidebar section heading"), 
			_("News Feeds"));
		$this->getConfig();
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
		
		if (file_exists($blog_feeds.$rss1_file) ||
		    file_exists($blog_feeds.$rss2_file)) {
			 	if ($this->header) { # Suppress empty header ?>
<h3><?php echo $this->header; ?></h3><?php 
				} ?>
<ul class="imglist">
<?php
			$ent = NewBlogEntry();
			if ($ent->isEntry()) {
				$comm_feeds_url = $ent->commentlink();
				$ent_path = $ent->localpath().PATH_DELIM.
				            ENTRY_COMMENT_DIR.PATH_DELIM;
				if (file_exists($ent_path.$rss1_comments) ) { 
?>
<li><a href="<?php echo $comm_feeds_url.$rss1_comments; ?>"><img src="<?php echo getlink("rss1_comments_button.png", LINK_IMAGE); ?>" alt="<?php p_("Comment links - RSS 1.0"); ?>" title="<?php p_("RSS 1.0 comment feed - links only"); ?>" /></a></li><?php
				}
				if (file_exists($ent_path.$rss2_comments) ) {
?>
<li><a href="<?php echo $comm_feeds_url.$rss2_comments; ?>"><img src="<?php echo getlink("rss2_comments_button.png", LINK_IMAGE); ?>" alt="<?php p_("Full comments - RSS 2.0"); ?>" title="<?php p_("RSS 2.0 comment feed - full comments"); ?>" /></a></li>
<?php
				}
			}  # End inner if
			if (file_exists($blog_feeds.$rss1_file)) {
?>
<li><a href="<?php echo $blog_feeds_url.$rss1_file; ?>"><img src="<?php echo getlink("rss1_button.png", LINK_IMAGE); ?>" alt="<?php p_("Entry links - RSS 1.0"); ?>" title="<?php p_("RSS 1.0 blog entry feed - links only"); ?>" /></a></li> <?php
			}
			if (file_exists($blog_feeds.$rss2_file)) { ?>
<li><a href="<?php echo $blog_feeds_url.$rss2_file; ?>"><img src="<?php echo getlink("rss2_button.png", LINK_IMAGE); ?>" alt="<?php p_("Full entires - RSS 2.0"); ?>" title="<?php p_("RSS 2.0 blog entry feed - full entries"); ?>" /></a></li><?php
			} ?>
</ul>
<?php 
		}  # End outer if
	}  # End function

} 

$newsfeeds = new News();
$newsfeeds->registerEventHandler("sidebar", "OnOutput", "output");
?>
