<?php if (defined("BLOG_ROOT")) {   # If there is no blog, skip all this. ?>
<h3><a href="<?php echo $BLOG_URL_ROOTREL.BLOG_ENTRY_PATH; ?>/">Archives</a></h3>
<ul>
<?php 

# Display a list of past months.  Set the variable on the next line to 
# change the number of months displayed.

$MAX_MONTHS_OF_ARCHIVES = 5;
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
</ul>
<?php
if (is_dir($BLOG_BASE_DIR.PATH_DELIM.BLOG_ARTICLE_PATH)) {
?>
<h3><a href="<?php echo $BLOG_URL_ROOTREL.BLOG_ARTICLE_PATH; ?>/">Articles</a></h3>
<ul>
<?php

$art_list = scan_directory($BLOG_BASE_DIR.PATH_DELIM.BLOG_ARTICLE_PATH, true);
$ret_list = array();

if (count($art_list) > 0) {

foreach ($art_list as $item) {
		$file = $BLOG_BASE_DIR.PATH_DELIM.BLOG_ARTICLE_PATH.PATH_DELIM.$item.PATH_DELIM.STICKY_PATH;
		if (file_exists($file)) {
			$text = file($file);
			$ret_list[$item] = $text[0];
		}
	}

}

if (count($ret_list) > 0) {
	foreach($ret_list as $dir=>$sub) { 
?>
<li><a href="<?php echo "$BLOG_URL_ROOTREL".BLOG_ARTICLE_PATH."/$dir/"; ?>"><?php echo $sub; ?></a></li>
<?php 
	}
} else { 
?>
<li></li>
<?php 
} ?>
</ul>
<?php } ?>
<h3>News Feeds</h3>
<ul>
<li><a href="<?php echo $BLOG_RSS1_FEED; ?>">Links - <abbr title="RDF Site Summary">RSS</abbr> 1.0</a></li>
<li><a href="<?php echo $BLOG_RSS2_FEED; ?>">Full entries - <abbr title="Rich Site Summary">RSS</abbr> 2.0</a></li>
</ul>
<?php 

# Check if the user is logged in and, if so, present administrative options.
if (check_login()) { 
?>
<h3>Weblog Administration</h3>
<ul>
<?php
	if (class_exists("BlogEntry")) {
		$ent = new BlogEntry();
		if ( $ent->isEntry(getcwd()) ) { 
?>
<li><a href="edit.php">Edit this post</a></li>
<li><a href="uploadfile.php">Upload file for this post</a></li>
<?php 
 		}
	}
?>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>new.php">Add new post</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>newart.php">Add new article</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>uploadfile.php">Upload file for blog</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>edit.php">Edit weblog settings</a></li>
<li><a href="<?php echo $BLOG_URL_ROOTREL; ?>logout.php">Logout</a></li>
</ul>
<?php 
# If the user isn't logged in, give him a login link.
} else { 
?>
<h3><a href="<?php echo $BLOG_URL_ROOTREL; ?>login.php">Login</a></h3>
<?php 
} 
?>
<?php } ?>
<a href="<?php echo PACKAGE_URL; ?>"><img alt="Powered by <?php echo PACKAGE_NAME; ?>" title="Powered by <?php echo PACKAGE_NAME; ?>" src="<?php echo THEME_IMAGES; ?>/logo.png" /></a>
