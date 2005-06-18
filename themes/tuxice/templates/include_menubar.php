<h2>Site map</h2>
<ul>
<?php 
$map_file = '';
if (defined("BLOG_ROOT") && is_file(BLOG_ROOT.PATH_DELIM."sitemap.html")) {
	$map_file = BLOG_ROOT.PATH_DELIM."sitemap.html";
} elseif (is_file(INSTALL_ROOT.PATH_DELIM."sitemap.html")) {
	$map_file = INSTALL_ROOT.PATH_DELIM."sitemap.html";
}
if ($map_file) {
	$data = file($map_file);
	foreach ($data as $line) { 
?>
<li><?php echo $line; ?></li>
<?php } 
} else { ?>
<li><a href="/" title="Skepticats.com">Home</a></li>
<li><a href="<?php echo PACKAGE_URL; ?>" title="<?php echo PACKAGE_DESCRIPTION; ?>"><?php echo PACKAGE_NAME; ?></a></li>
<li><a href="/rox/" title="Software and information for the ROX desktop environment">The Joy of ROX</a></li>
<li><a href="/linlog/" title="The Skepticats' Linux Weblog">LinLog</a></li>
<?php } ?>
</ul>
<a href="http://www.free-penguin.org/"><img alt="Free cuddling" title="Free-Penguin.org: To cuddle is free!" src="<?php echo getlink("freecuddling.png"); ?>" /></a>
