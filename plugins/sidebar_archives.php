<?php

# Plugin: Archives
# A sidebar plugin to display months of archives.
#
# This plugin provides quick, easy access to blog archives.  It produces a
# sidebar panel with a link for each month for which there are entries, up
# to a configurable maximum.

class Archives extends Plugin {

	function __construct($do_output=0) {
		global $SYSTEM;
		$this->plugin_name = _("List the months of archives for a blog.");
		$this->plugin_version = "0.2.1";
		$this->addOption("max_months", _("Number of months to show"), 6, "text");
		$this->addOption("title", _("Sidebar section title"), 
		                 _("Archives"), "text");
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
			$this->registerEventHandler("sidebar", "OnOutput", "output");
		}
		
		if ($do_output) $this->output();
	}

	function output($parm=false) {
		$blg = NewBlog();
		if (! $blg->isBlog()) return false;
		
		$root = $blg->getURL();
		$month_list = $blg->getRecentMonthList($this->max_months);
?>
<?php if ($this->title) { ?>
<h3><a href="<?php echo $root.BLOG_ENTRY_PATH; ?>/"><?php echo $this->title; ?></a></h3>
<?php } ?>
<ul>
<?php
		foreach ($month_list as $month) {
			$ts = mktime(0, 0, 0, $month["month"], 1, $month["year"]);
			if (USE_STRFTIME) $link_desc = fmtdate("%B %Y", $ts);
			else              $link_desc = fmtdate("F Y", $ts);
?>
<li><a href="<?php echo $month["link"]; ?>"><?php echo $link_desc; ?></a></li>
<?php 
		}  # End of foreach loop.
?>
<li><a href="<?php echo $blg->uri('listall'); ?>"><?php p_("Show all entries"); ?></a></li>
</ul><?php
	}  # End of function
}

global $PLUGIN_MANAGER;
if (! $PLUGIN_MANAGER->plugin_config->value('archives', 'creator_output', 0)) {
	$rec =& new Archives();
}

?>
