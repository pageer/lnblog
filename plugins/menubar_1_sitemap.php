<?php
class SiteMap extends Plugin {

	var $link_file;

	function SiteMap() {
		$this->plugin_desc = _("Show a sitemap in the menubar");
		$this->plugin_version = "0.1.0";
		$this->link_file = SITEMAP_FILE;
		$this->member_list = array("link_file"=>_("Name of link-list file"));
	}

	function output($parm=false) {
		$map_file = '';
		$blog = NewBlog();
		if ($blog->isBlog() && 
	   	 is_file(BLOG_ROOT.PATH_DELIM.$this->link_file)) {
			$map_file = BLOG_ROOT.PATH_DELIM.$this->link_file;
		} elseif (is_file(INSTALL_ROOT.PATH_DELIM.USER_DATA.
		                  PATH_DELIM.$this->link_file)) {
			$map_file = INSTALL_ROOT.PATH_DELIM.USER_DATA.
			            PATH_DELIM.$this->link_file;
		}
?>
<h2>Site map</h2>
<ul>
<?php 
if ($map_file) {
	$data = file($map_file);
	foreach ($data as $line) { 
		if (trim($line) != '') {
?>
<li><?php echo $line; ?></li>
<?php }
	}
} else { ?>
<li><a href="/" title="<?php p_("Site home page"); ?>"><?php p_("Home"); ?></a></li>
<?php } ?>
</ul>
<?php
	}
}
$map = new SiteMap();
$map->registerEventHandler("menubar", "OnOutput", "output");
?>
