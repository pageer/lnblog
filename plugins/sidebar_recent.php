<?php  
class Recent extends Plugin {

	function Recent($do_output=0) {
		global $SYSTEM;
		
		$this->plugin_desc = _("Show some of the more recent posts in the sidebar.");
		$this->plugin_version = "0.2.0";
		$this->addOption("old_header", 
			_("Header for main blog page (entries not on main page)"), 
			_("Older Entries"));
		$this->addOption("recent_header", 
			_("Header for other pages (newest entries)"), 
			_("Recent Entries"));
		$this->addOption("num_entries", 
			_("Number of entries to show"), "Blog default", "text");
		$this->addOption("show_main",
			_("Show link to main blog page"), true, "checkbox");
		
		$this->addOption('no_event',
			_('No event handlers - do output when plugin is created'),
			$SYSTEM->sys_ini->value("plugins","EventDefaultOff", 0), 
			'checkbox');
			
		$this->getConfig();
		
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
		if ( !($this->num_entries > 0) ) $this->num_entries = false; 
		
		# Show some of the more recent entries.  If we're on the "front page"
		# of the blog, then show the next set of entries.  Otherwise, show the 
		# most recent entries.
		$is_index = ( current_url() == $blg->uri('blog') || 
		              current_url() == $blg->uri('blog')."index.php");

		if ($is_index) {
			$next_list = $blg->getNextMax($this->num_entries);
		} else {
			$next_list = $blg->getRecent($this->num_entries);
		}

		if ( count($next_list) > 0 ) { 
			if ($is_index) {
?>
<h3><a href="<?echo $blg->getURL(); ?>"><?php echo htmlspecialchars($this->old_header); ?></a></h3>
<?php 
			} else { # !$is_index 
?>
<h3><a href="<?echo $blg->getURL(); ?>"><?php echo htmlspecialchars($this->recent_header); ?></a></h3>
<?php 
			} # End inner if
?>
<ul>
<?php 
			foreach ($next_list as $ent) { 
?>
<li><a href="<?php echo $ent->permalink(); ?>"><?php echo htmlspecialchars($ent->subject); ?></a></li>
<?php 
			}	 # End foreach
			if ($this->show_main) { /* Link to main page */ ?>
<li style="margin-top: 0.5em"><a href="<?php echo $blg->getURL();?>"><?php p_("Show home page");?></a></li><?php 
			} ?>
</ul>
<?php 
		}  # End outer if
	} #End function init_output

}

global $PLUGIN_MANAGER;
if (! $PLUGIN_MANAGER->plugin_config->value('recent', 'creator_output', 0)) {
	$rec =& new Recent();
}

?>
