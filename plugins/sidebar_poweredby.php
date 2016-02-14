<?php
# Plugin: LnBlogAd
# A simple advertising banner.  This just displays a little icon and link
# that tells people you're using LnBlog.
class LnBlogAd extends Plugin {

	function __construct($do_output=0) {
		$this->plugin_desc = _("Shameless link whoring.  Put a link to LnBlog on the page.");
		$this->plugin_version = "0.2.1";
		$this->use_footer = false;
		$this->addOption("use_footer",
			_("Put the advertising in the footer, not the sidebar"),
			false, "checkbox");

		$this->addOption('no_event',
			_('No event handlers - do output when plugin is created'),
			System::instance()->sys_ini->value("plugins","EventDefaultOff", 0), 
			'checkbox');

		parent::__construct();

		if ( $this->no_event || 
		     System::instance()->sys_ini->value("plugins","EventForceOff", 0) ) {
			# If either of these is true, then don't set the event handler
			# and rely on explicit invocation for output.
		} else {
			if ($this->use_footer) {
				$this->registerEventHandler("footer", "OnOutput", "footer_output");
			} else {
				$this->registerEventHandler("sidebar", "OnOutput", "output");
			}
		}
		
		if ($do_output) {
			if ($this->use_footer) $this->footer_output();
			else $this->output();
		}
	}
	
	function output() { ?>
<div class="panel powered-by">
<a href="<?php echo PACKAGE_URL; ?>"><img alt="<?php pf_("Powered by %s", PACKAGE_NAME); ?>" title="<?php pf_("Powered by %s", PACKAGE_NAME); ?>" src="<?php echo getlink("logo.png", LINK_IMAGE); ?>" /></a>
</div>
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

if (! PluginManager::instance()->plugin_config->value('lnblogad', 'creator_output', 0)) {
	$plug = new LnBlogAd();
}