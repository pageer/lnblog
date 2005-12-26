<?php  
class TagList extends Plugin {

	function TagList() {
		$this->plugin_desc = _("Provides a list of links to view post for each tag used in the blog.");
		$this->plugin_version = "0.1.0";
		$this->addOption("header", _("Sidebar section heading"), _("Topics"));
		$this->getConfig();
	}

	function output($parm=false) {
		
		$blg = NewBlog();
		if (! $blg->isBlog() ) return false;
		if (empty($blg->tag_list)) return false;
		if ($this->header) {
?><h3><?php echo $this->header;?></h3><?php } ?>
<ul><?php
		foreach ($blg->tag_list as $tag) { ?>
	<li><a href="<?php echo $blg->getURL(); ?>tags.php?tag=<?php echo $tag;?>"><?php echo $tag;?></a></li><?php
		} ?>
</ul><?php
	}

}

$tgl = new TagList();
$tgl->registerEventHandler("sidebar", "OnOutput", "output");
?>
