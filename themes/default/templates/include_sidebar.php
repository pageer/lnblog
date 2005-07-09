<?php if (defined("BLOG_ROOT")) {   # If there is no blog, skip all this. 

# Get a blog entry for us to work with.  If one exists, then use it.
if (isset($blog) && get_class($blog) == "Blog") {
	# We already have a defined blog, so do nothing.
} else {
	$blog = new Blog();
}

# Do we *really* want to include the BlogEntry class just so that the
# sidebar remains consistent?
require_once("blogentry.php");
if (class_exists("BlogEntry")) $next_list = $blog->getNextMax();
else $next_list = array();

if ( count($next_list) > 0 ) {
?>
<h3><a href="<?echo $BLOG_URL_ROOTREL; ?>">Recent Entries</a></h3>
<ul>
<?php foreach ($next_list as $ent) { ?>
<li><a href="<?php echo $ent->permalink(); ?>"><?php echo $ent->subject; ?></a></li>
<?php } ?>
</ul>
<?php } ?>
<?php # List articles for this blog.

$art_list = scan_directory($BLOG_BASE_DIR.PATH_DELIM.BLOG_ARTICLE_PATH, true);
$ret_list = array();

if (count($art_list) > 0 && $art_list !== false) {

	foreach ($art_list as $item) {
		$file = $BLOG_BASE_DIR.PATH_DELIM.BLOG_ARTICLE_PATH.PATH_DELIM.$item.PATH_DELIM.STICKY_PATH;
		if (file_exists($file)) {
			$text = file($file);
			$ret_list[$item] = $text[0];
		}
	}
}

if (count($ret_list) > 0) { ?>
<h3><a href="<?php echo $BLOG_URL_ROOTREL.BLOG_ARTICLE_PATH; ?>/">Articles</a></h3>
<ul>
<?php
	foreach($ret_list as $dir=>$sub) { 
?>
<li><a href="<?php echo "$BLOG_URL_ROOTREL".BLOG_ARTICLE_PATH."/$dir/"; ?>"><?php echo $sub; ?></a></li>
<?php 
	} ?>
</ul>
<?php
}
?>
<h3><a href="<?php echo $BLOG_URL_ROOTREL.BLOG_ENTRY_PATH; ?>/">Archives</a></h3>
<ul>
<?php 

# Display a list of past months.  Set the variable on the next line to 
# change the number of months displayed.

$MAX_MONTHS_OF_ARCHIVES = 6;
$year_list = scan_directory($BLOG_BASE_DIR.PATH_DELIM.BLOG_ENTRY_PATH, true);

rsort($year_list);
$num_months = 0;

foreach ($year_list as $year) { 

	$month_list = scan_directory($BLOG_BASE_DIR.PATH_DELIM.BLOG_ENTRY_PATH.PATH_DELIM.$year, true);
	rsort($month_list);
	
	foreach ($month_list as $month) {
		$ts = mktime(0, 0, 0, $month, 1, $year);
		$link_desc = strftime("%B %Y", $ts);
		$link_url = $BLOG_URL_ROOTREL.BLOG_ENTRY_PATH."/".$year."/".$month."/";
?>
<li><a href="<?php echo $link_url; ?>"><?php echo $link_desc; ?></a></li>
<?php 
		$num_months++;
		if ($num_months >= $MAX_MONTHS_OF_ARCHIVES) break 2;
	} 
}

?>
<li><a href="<?php echo $BLOG_URL_ROOTREL.BLOG_ENTRY_PATH."/all.php"; ?>">Show all entries</a></li>
</ul>

<h3>News Feeds</h3>
<ul class="imglist">
<?php if (isset($ENTRY_RSS1_FEED)) { ?>
<li><a href="<?php echo $ENTRY_RSS1_FEED; ?>"><img src="<?php echo getlink("rss1_comments_button.png", LINK_IMAGE); ?>" alt="Comment links - RSS 1.0" title="RSS 1.0 comment feed - links only" /></a></li>
<li><a href="<?php echo $ENTRY_RSS2_FEED; ?>"><img src="<?php echo getlink("rss2_comments_button.png", LINK_IMAGE); ?>" alt="Full comments - RSS 2.0" title="RSS 2.0 comment feed - full comments" /></a></li>
<?php } ?>
<li><a href="<?php echo $BLOG_RSS1_FEED; ?>"><img src="<?php echo getlink("rss1_button.png", LINK_IMAGE); ?>" alt="Entry links - RSS 1.0" title="RSS 1.0 blog entry feed - links only" /></a></li>
<li><a href="<?php echo $BLOG_RSS2_FEED; ?>"><img src="<?php echo getlink("rss2_button.png", LINK_IMAGE); ?>" alt="Full entires - RSS 2.0" title="RSS 2.0 blog entry feed - full entries" /></a></li>
</ul>
<?php 

# Check if the user is logged in and, if so, present administrative options.
$usr = new User();
if ($usr->checkLogin()) { 
?>
<h3>Weblog Administration</h3>
<ul>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>new.php">Add new post</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>newart.php">Add new article</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>uploadfile.php">Upload file for blog</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>edit.php">Edit weblog settings</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>map.php">Edit custom sitemap</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>useredit.php">Edit User Information</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>logout.php">Logout <?php echo $usr->username(); ?></a></li>
</ul>
<?php 
# If the user isn't logged in, give him a login link.
} else { 
?>
<h3><a href="<?php echo $BLOG_URL_ROOTREL; ?>login.php">Login</a></h3>
<?php 
} 
?>
<?php } # This is where we end BLOG_ROOT condition on first line. ?>
<a href="<?php echo PACKAGE_URL; ?>"><img alt="Powered by <?php echo PACKAGE_NAME; ?>" title="Powered by <?php echo PACKAGE_NAME; ?>" src="<?php echo getlink("logo.png", LINK_IMAGE); ?>" /></a>
