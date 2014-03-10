<?php 

# Plugin: PageHeader
# Adds header text to the page banner.
#
# This plugin shows the blog name as a heading link in the banner.
# There is also an option to show the blog description as a lesser heading.

class PageHeader extends Plugin {

	function __construct($do_output=0) {
		global $SYSTEM;

		$this->plugin_desc = _("Output a banner for the page.");
		$this->plugin_version = "0.2.2";
		$this->show_desc = false;
		$this->addOption("show_desc", _("Show description"), false,"checkbox");

		$this->addOption('no_event',
			_('No event handlers - do output when plugin is created'),
			$SYSTEM->sys_ini->value("plugins","EventDefaultOff", 0), 
			'checkbox');

		parent::__construct();

		if ( $this->no_event || 
		     $SYSTEM->sys_ini->value("plugins","EventForceOff", 0) ) {
			# If either of these is true, then don't set the event handler
			# and rely on explicit invocation for output.
		} else {
			$this->registerEventHandler("banner", "OnOutput", "output");
		}
		
		if ($do_output) $this->output();
	}

	function output($parm=false) {
		$blg = NewBlog();
		if ($blg->isBlog()) { 
			?>
<h1><a href="<?php echo $blg->uri('blog'); ?>" title="<?php echo $blg->description; ?>"><?php echo $blg->name; ?></a></h1>
<?php 
			if ($this->show_desc) { ?>
<h3><?php echo $blg->description;?></h3>
<?php
			}
		} else { 
?>
<h1><a href="<?php echo PACKAGE_URL; ?>"><?php echo PACKAGE_NAME; ?></a></h1>
<?php 
			if ($this->show_desc) { ?>
<h3><?php echo PACKAGE_DESCRIPTION; ?></h3>
<?php 
			}
		} 
	}
}

global $PLUGIN_MANAGER;
if (! $PLUGIN_MANAGER->plugin_config->value('pageheader', 'creator_output', 0)) {
	$plug = new PageHeader();
}