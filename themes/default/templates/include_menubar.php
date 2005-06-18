<h2>Site map</h2>
<ul>
<?php 
$map_file = '';
if (defined("BLOG_ROOT") && is_file(BLOG_ROOT.PATH_DELIM.SITEMAP_FILE)) {
	$map_file = BLOG_ROOT.PATH_DELIM.SITEMAP_FILE;
} elseif (is_file(INSTALL_ROOT.PATH_DELIM.SITEMAP_FILE)) {
	$map_file = INSTALL_ROOT.PATH_DELIM.SITEMAP_FILE;
}
if ($map_file) {
	$data = file($map_file);
	foreach ($data as $line) { 
		if (trim($line) != '') {
?>
<li><?php echo $line; ?></li>
<?php }
	}
} else { ?>
<li><a href="/" title="Site home page">Home</a></li>
<li><a href="<?php echo PACKAGE_URL; ?>" title="<?php echo PACKAGE_DESCRIPTION; ?>"><?php echo PACKAGE_NAME; ?></a></li>
<?php } ?>
</ul>
