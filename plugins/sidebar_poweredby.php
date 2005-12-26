<?php
class LnBlogAd extends Plugin {

	function LnBlogAd() {
		$this->plugin_desc = _("Shameless link whoring.");
		$this->plugin_version = "0.1.1";
		$this->use_footer = false;
		/*
		$this->member_list = array();
		$this->member_list["use_footer"] = 
			array("description"=>_("Put the advertising in the footer, not the sidebar"),
			      "control"=>"checkbox",
			      "default"=>false);
		$this->getConfig();
		*/
	}
	
	function output() { ?>
<a href="<?php echo PACKAGE_URL; ?>"><img alt="<?php pf_("Powered by %s", PACKAGE_NAME); ?>" title="<?php pf_("Powered by %s", PACKAGE_NAME); ?>" src="<?php echo getlink("logo.png", LINK_IMAGE); ?>" /></a>
<?php 
	}	

	function footer_output() { 
		$blg = NewBlog();
		if ($blg->isBlog()) {
			$message = spf_("%1\s is powered by %2\s", $blg->name, 
			                '<a href="'.PACKAGE_URL.'">'.PACKAGE_NAME.'</a>');
		} else {
			$message = spf_("Powered by %2\s", 
			                '<a href="'.PACKAGE_URL.'">'.PACKAGE_NAME.'</a>');
		}
		echo $message;
	}
}

$login = new LnBlogAd();
if ($login->use_footer) {
	$login->registerEventHandler("footer", "OnOutput", "footer_output");
} else {
	$login->registerEventHandler("sidebar", "OnOutput", "output");
}
?>
