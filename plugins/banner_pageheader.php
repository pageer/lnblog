<?php 

# Plugin: PageHeader
# This plugin adds header text to the page banner.  It can show the blog
# name as a heading link in the banner and optionally show the blog description as a lesser heading.

class PageHeader extends Plugin {

	public function __construct($do_output=0) {

		$this->plugin_desc = _("Output a banner for the page.");
		$this->plugin_version = "0.2.4";
		$this->show_desc = false;
		$this->addOption("show_desc", _("Show description"), false,"checkbox");

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
			$this->registerEventHandler("banner", "OnOutput", "output");
		}
		
		if ($do_output) $this->output();
	}

	public function output($parm=false) {
		$blg = NewBlog();
		if ($blg->isBlog()): ?>
<h1><a href="<?php echo $blg->uri('blog')?>" title="<?php echo $blg->description?>"><?php echo $blg->name; ?></a></h1><?php
			if ($this->show_desc): ?>
<h3><?php echo $blg->description;?></h3><?php
			endif;
		else: ?>
<h1><a href="<?php echo PACKAGE_URL?>"><?php echo PACKAGE_NAME?></a></h1><?php 
			if ($this->show_desc): ?>
<h3><?php echo PACKAGE_DESCRIPTION?></h3><?php 
			endif;
		endif;
	}
}

if (! PluginManager::instance()->plugin_config->value('pageheader', 'creator_output', 0)) {
	$plug = new PageHeader();
}