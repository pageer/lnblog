<?php if (defined("BLOG_ROOT")) { ?>
<h3><a href="<?php echo $BLOG_URL_ROOTREL.BLOG_ENTRY_PATH; ?>/">Archives</a></h3>
<?php if (0) { ?><h3><a href="<?php echo $BLOG_URL_ROOTREL.BLOG_ARTICLE_PATH; ?>/">Articles</a></h3><?php } ?>
<h3>Syndicate</h3>
<ul>
<li><a href="<?php echo $BLOG_RSS1_FEED; ?>" title="RSS 1.0">Links</a></li>
<li><a href="<?php echo $BLOG_RSS2_FEED; ?>" title="RSS 2.0">Entries</a></li>
</ul>
<h3>Administration</h3>
<ul>
<?php 
# Check if the user is logged in and, if so, present administrative options.
if (check_login()) { 
	if (class_exists("BlogEntry")) {
		$ent = new BlogEntry();
		if ( $ent->isEntry(getcwd()) ) { 
?>
<li><a href="edit.php">Edit this post</a></li>
<li><a href="uploadfile.php">Upload to entry</a></li>
<?php 
 		}
	}
?>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>new.php">Add new post</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>newart.php">Add new article</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>uploadfile.php">Upload to weblog</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>edit.php">Edit weblog settings</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>logout.php">Logout</a></li>
<?php 
# If the user isn't logged in, give him a login link.
} else { 
?>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>login.php">Login</a></li>
<?php 
} 
?>
</ul>
<?php } ?>
<a href="http://www.skepticats.com/basicblog/"><img alt="Powered by <?php echo PACKAGE_NAME; ?>" title="Powered by <?php echo PACKAGE_NAME; ?>" src="<?php echo THEME_IMAGES; ?>/logo.png" /></a>
