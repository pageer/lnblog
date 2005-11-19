<?php
class News extends Plugin {

	function News() {
		$this->plugin_name = _("List the RSS feeds for the current page.");
		$this->plugin_version = "0.1.0";
	}

	function output() {
		$blg = NewBlog();
		if (! $blg->isBlog() ) return false;
		
		$blog_feeds = BLOG_ROOT.PATH_DELIM.BLOG_FEED_PATH.PATH_DELIM;
		$blog_feeds_url = $blg->getURL().BLOG_FEED_PATH."/";
		
		if (file_exists($blog_feeds.BLOG_RSS1_NAME) ||
		    file_exists($blog_feeds.BLOG_RSS2_NAME)) {
?>
<h3><?php p_("News Feeds"); ?></h3>
<ul class="imglist">
<?php
			$ent = NewBlogEntry();
			if ($ent->isEntry()) {
				$comm_feeds_url = $ent->commentlink();
				$ent_path = $ent->localpath().PATH_DELIM.
				            ENTRY_COMMENT_DIR.PATH_DELIM;
				if (file_exists($ent_path.COMMENT_RSS1_PATH) ) { 
?>
<li><a href="<?php echo $comm_feeds_url.COMMENT_RSS1_PATH; ?>"><img src="<?php echo getlink("rss1_comments_button.png", LINK_IMAGE); ?>" alt="<?php p_("Comment links - RSS 1.0"); ?>" title="<?php p_("RSS 1.0 comment feed - links only"); ?>" /></a></li><?php
				}
				if (file_exists($ent_path.COMMENT_RSS2_PATH) ) {
?>
<li><a href="<?php echo $comm_feeds_url.COMMENT_RSS2_PATH; ?>"><img src="<?php echo getlink("rss2_comments_button.png", LINK_IMAGE); ?>" alt="<?php p_("Full comments - RSS 2.0"); ?>" title="<?php p_("RSS 2.0 comment feed - full comments"); ?>" /></a></li>
<?php
				}
			}  # End inner if
			if (file_exists($blog_feeds.BLOG_RSS1_NAME)) {
?>
<li><a href="<?php echo $blog_feeds_url.BLOG_RSS1_NAME; ?>"><img src="<?php echo getlink("rss1_button.png", LINK_IMAGE); ?>" alt="<?php p_("Entry links - RSS 1.0"); ?>" title="<?php p_("RSS 1.0 blog entry feed - links only"); ?>" /></a></li> <?php
			}
			if (file_exists($blog_feeds.BLOG_RSS2_NAME)) { ?>
<li><a href="<?php echo $blog_feeds_url.BLOG_RSS2_NAME; ?>"><img src="<?php echo getlink("rss2_button.png", LINK_IMAGE); ?>" alt="<?php p_("Full entires - RSS 2.0"); ?>" title="<?php p_("RSS 2.0 blog entry feed - full entries"); ?>" /></a></li><?php
			} ?>
</ul>
<?php 
		}  # End outer if
	}  # End function

} 

$newsfeeds = new News();
$newsfeeds->registerEventHandler("sidebar", "OnOutput", "output");
?>
