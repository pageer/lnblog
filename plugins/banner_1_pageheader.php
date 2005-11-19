<?php 
class PageHeader extends Plugin {

	function PageHeader() {
		$this->plugin_desc = _("Output a banner for the page.");
		$this->plugin_version = "0.1.0";
	}

	function output($parm=false) {
		$blg = NewBlog();
		if ($blg->isBlog()) { 
			?>
<h1><a href="<?php echo $blg->getURL(); ?>" title="<?php echo $blg->description; ?>"><?php echo $blg->name; ?></a></h1>
<?php 
		} else { 
?>
<h1><a href="<?php echo PACKAGE_URL; ?>"><?php echo PACKAGE_NAME; ?></a></h1>
<?php 
		} 
	}
}

$bann = new PageHeader();
$bann->registerStaticEventHandler("banner", "OnOutput", "output");
?>
