<?php
class LnBlogAd extends Plugin {

	function LnBlogAd() {
		$this->plugin_desc = _("Shameless link whoring.");
		$this->plugin_version = "0.1.0";
	}
	
	function output() { ?>
<a href="<?php echo PACKAGE_URL; ?>"><img alt="<?php p_("Powered by %s", PACKAGE_NAME); ?>" title="<?php p_("Powered by %s", PACKAGE_NAME); ?>" src="<?php echo getlink("logo.png", LINK_IMAGE); ?>" /></a>
<?php 
	}	
}

$login = new LnBlogAd();
$login->registerStaticEventHandler("sidebar", "OnOutput", "output");
?>