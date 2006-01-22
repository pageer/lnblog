<?php
class SiteMap extends Plugin {

	var $link_file;

	function SiteMap() {
		$this->plugin_desc = _("Show a sitemap in the menubar");
		$this->plugin_version = "0.2.1";
		$this->list_header = _("Site Map");
		$this->no_markup = false;
		$this->addOption("link_file", _("Name of link-list file"),
		                 "sitemap.htm", "text");
		$this->addOption("list_header", _("Heading at start of menubar"),
		                 _("Site Map"), "text");
		$this->addOption("no_markup", _("Use unmodified file contents as menubar (no auto-generated HTML)"),
		                 false, "checkbox");
		$this->getConfig();
	}

	function output($parm=false) {
		$map_file = '';
		$blog = NewBlog();
		if ($blog->isBlog() && 
	   	 file_exists(BLOG_ROOT.PATH_DELIM.$this->link_file)) {
			$map_file = BLOG_ROOT.PATH_DELIM.$this->link_file;
		} elseif (is_file(mkpath(USER_DATA_PATH,$this->link_file))) {
			$map_file = mkpath(USER_DATA_PATH,$this->link_file);
		}
		if ($this->no_markup && is_file($map_file)) {
			$data = file($map_file);
			foreach ($data as $line) echo $line;
		} else {
?>
<h2><?php echo $this->list_header; ?></h2>
<ul>
<?php 
			if (is_file($map_file)) {
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
