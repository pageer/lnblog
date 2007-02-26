<?php

# Plugin: Articles
#

class Articles extends Plugin {

	function Articles($do_output=0) {
		global $SYSTEM;
		$this->plugin_desc = _("List the articles for a blog.");
		$this->plugin_version = "0.2.4";
		$this->header = _("Articles");
		$this->static_link = true;
		$this->custom_links = "links.htm";
		$this->addOption("header", _("Sidebar section heading"),
			_("Articles"), "text");
		$this->addOption("static_link", 
			_("Show link to list of static articles"),
			true, "checkbox");
		$this->addOption("showall_text",
			_("Text for link to all static articles"),
			_("All static pages"), "text");
		$this->addOption("custom_links", 
			_("File where additional links to display are stored"),
			"links.htm", "true");
			
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
		global $SYSTEM;
		
		$blg = NewBlog();
		$u = NewUser();
		if (! $blg->isBlog()) return false;
		
		$art_list = $blg->getArticleList();
		$tpl = NewTemplate("sidebar_panel_tpl.php");
		$items = array();

		if (count($art_list) > 0) {
		
			if ($this->header) {
				$tpl->set("PANEL_TITLE",
				          ahref($blg->uri('articles'), $this->header));
			}
			
			foreach ($art_list as $art) {
				$items[] = ahref($art['link'], htmlspecialchars($art['title']));
			}
			
			if ( is_file(mkpath($blg->home_path, $this->custom_links)) ) {
				$data = file(mkpath($blg->home_path, $this->custom_links));
				foreach ($data as $line) $items[] = $line;
			}
			
			if ($this->static_link) {
				$items[] = array('description'=>ahref($blg->uri('articles'), $this->showall_text),
				                 'style'=>'margin-top: 0.5em');
			} 
		
			if ($SYSTEM->canModify($blg, $u)) {
				$items[] = array('description'=>ahref($blg->uri('editfile', $this->custom_links),
				                                      _("Add custom links")),
				                 'style'=>'margin-top: 0.5em');
			}

			$tpl->set('PANEL_LIST', $items);
			echo $tpl->process();
		}
	}  # End function
	
}

global $PLUGIN_MANAGER;
if (! $PLUGIN_MANAGER->plugin_config->value('articles', 'creator_output', 0)) {
	$art =& new Articles();
}
?>
