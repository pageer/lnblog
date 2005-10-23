<?php
class LnBlogAd extends Plugin {

	function LnBlogAd() {
		$this->plugin_desc = "Shameless link whoring.";
		$this->plugin_version = "0.1.0";
	}
	
	function output() { ?>
<a href="<?php echo PACKAGE_URL; ?>"><img alt="Powered by <?php echo PACKAGE_NAME; ?>" title="Powered by <?php echo PACKAGE_NAME; ?>" src="<?php echo getlink("logo.png", LINK_IMAGE); ?>" /></a>
<?php 
	}	
}

$login = new LnBlogAd();
$login->registerStaticEventHandler("sidebar", "OnOutput", "output");
?>