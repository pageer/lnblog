<?php
class SiteMap extends Plugin {

	var $link_file;

	function SiteMap() {
		$this->plugin_desc = _("Show a sitemap in the menubar");
		$this->plugin_version = "0.2.0";
		$this->link_file = SITEMAP_FILE;
		$this->list_header = _("Site Map");
		$this->no_markup = false;
		$this->member_list = array();
		$this->member_list["link_file"] =
			array("description"=>_("Name of link-list file"),
			      "default"=>SITEMAP_FILE);
		$this->member_list["list_header"] = 
			array("description"=>_("Heading at start of menubar"),
			      "default"=>_("Site Map"));
		$this->member_list["no_markup"] = 
			array("description"=>_("Use unmodified file contents as menubar (no auto-generated HTML)"),
			      "control"=>"checkbox",
			      "default"=>false);
		$this->controls = array("no_markup"=>"checkbox");
		$this->getConfig();
	}

	function output($parm=false) {
		$map_file = '';
		$blog = NewBlog();
		if ($blog->isBlog() && 
	   	 file_exists(BLOG_ROOT.PATH_DELIM.$this->link_file)) {
			$map_file = BLOG_ROOT.PATH_DELIM.$this->link_file;
		} elseif (is_file(mkpath(INSTALL_ROOT,USER_DATA,$this->link_file))) {
			$map_file = mkpath(INSTALL_ROOT,USER_DATA,$this->link_file);
		}
		if ($this->no_markup && $map_file) {
			$data = file($map_file);
			foreach ($data as $line) echo $line;
		} else {
?>
<h2><?php echo $this->list_header; ?></h2>
<ul>
<?php 
			if ($map_file) {
				$data = file($map_file);
				foreach ($data as $line) { 
					if (trim($line) != '') {
?>
<li><?php echo $line; ?></li>
<?php
					}
				}
			} else { ?>
<li><a href="/" title="<?php p_("Site home page"); ?>"><?php p_("Home"); ?></a></li>
<?php 
			} ?>
</ul>
<?php
		}
	}
}
$map = new SiteMap();
$map->registerEventHandler("menubar", "OnOutput", "output");
?>
