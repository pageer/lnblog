<?php

# Add this really massive if statements to that we don't end up declaring the 
# same class twice, i.e. if the page is called directly, this class will be defined 
# when it first loads and then again when the plugins are loaded.
if (! class_exists("Blogroll")) {  # Start massive if statement

require_once('lib/xml.php');
class Blogroll extends Plugin {

	function Blogroll($do_output=false) {
		global $SYSTEM;
	
		$this->plugin_version = "0.1.0";
		$this->plugin_desc = _("Creates a blogroll from an OPML file.");
		$this->addOption("file", _("Blog roll file (in OPML format)"), '');
		$this->addOption("caption",
		                 _("Caption for blogroll sidebar panel"),
		                 _("Blogroll"));
		$this->addOption("page_title",
		                 _("Heading when viewing the full page"),
		                 _("Other blogs of interest"));
		
		$this->addOption('no_event',
			_('No event handlers - do output when plugin is created'),
			$SYSTEM->sys_ini->value("plugins","EventDefaultOff", 0), 
			'checkbox');

		$this->link_only = true;

		$this->getConfig();

		if ( $this->no_event || 
		     $SYSTEM->sys_ini->value("plugins","EventForceOff", 0) ) {
			# If either of these is true, then don't set the event handler
			# and rely on explicit invocation for output.
		} else {
			$this->registerEventHandler("sidebar", "OnOutput", "output");
		}
		$this->registerEventHandler("page", "OnOutput", "add_stylesheet");
		
		if ($do_output) $this->output();
	}
	
	function get_file_path(&$blog) {
		$file = mkpath($blog->home_path, $this->file);
		if ($this->file && file_exists($file)) return $file;
		else return false;
	}
	
	function output_opml() {
	
		$b = NewBlog();
		$file = $this->get_file_path($b);
		
		if (! file_exists($file)) return false;
		
		$parser = new SimpleXMLReader($file);
		$parser->parse();

		foreach ($parser->domtree['children'] as $child) {
			if ($child['tag'] == 'body') {
				$outlines = &$child['children'];
			}
		}
		if (! isset($outlines)) return false;
		$this->dump_outline($outlines);
	}
	
	function dump_outline($ol) {
		if (! isset($ol)) return false;
	
		echo "<ul>\n";
		foreach ($ol as $item) {
			echo "<li>\n";
			if (isset($item['attributes']['XMLURL'])) {
				$this->show_item($item);
			} else {
				echo htmlspecialchars($item['attributes']['TITLE'])."<br />\n";
				$this->dump_outline($item['children']);
			}
			echo "</li>\n";
		}
	
		echo "</ul>\n";
	
	}
	
	function show_item(&$item) {
		$title = htmlspecialchars($item['attributes']['TITLE']);
		$html_url = ($item['attributes']['HTMLURL']);
		$rss_url = ($item['attributes']['XMLURL']);
		$description = htmlspecialchars($item['attributes']['DESCRIPTION']);
	
	?>
		<a href="<?php echo $html_url;?>" title="<?php echo $description;?>"><?php echo $title;?></a>
	<?php
		if (! $this->link_only) {
		?>
		(<a href="<?php echo $rss_url;?>">RSS</a>)<br />
		<div class="description"><?php echo $description;?></div>
	
		<?php
		}
	}

	function output() {
		$blog = NewBlog();
		
		if (! file_exists($this->get_file_path($blog))) return false;
		
		$tpl = NewTemplate("sidebar_panel_tpl.php");
		if ($this->caption) {
			if ($blog->isBlog()) {
				$tpl->set('TITLE_LINK', 
				          $blog->uri('plugin', 
				                     str_replace(".php", '', basename(__FILE__)), 
				                     array('show'=>'yes')));
			}
			$tpl->set('PANEL_TITLE', $this->caption);
		}
		$tpl->set('PANEL_ID', 'blogroll');
		
		ob_start();
		$this->output_opml();
		$tpl->set('PANEL_CONTENT', ob_get_contents());
		ob_end_clean();
		echo $tpl->process();
	}

	function output_page() {
		global $PAGE;
		$blog = NewBlog();
		$PAGE->setDisplayObject($blog);

		ob_start();
		echo "<h3>".$this->page_title."</h3>\n";
		$this->link_only = false;
		$this->output_opml();
		$body = ob_get_contents();
		ob_end_clean();

		$PAGE->title = spf_("Blogroll - ", $blog->name);
		$PAGE->display($body, $blog);
	}
	
	function add_stylesheet() {
		global $PAGE;
		ob_start();
?>
.description {
	font-size: 80%;
}

#blogroll ul li ul li {
	list-style-type: square;
	list-style-position: inside;
}

#blogroll ul {
	margin: 0;
}
<?php
		$data = ob_get_contents();
		ob_end_clean();
		$PAGE->addInlineStylesheet($data);
	}

}

} /* End massive if statement */

if (defined("PLUGIN_DO_OUTPUT")) {
	$plug = new Blogroll();
	$plug->output_page();
} else {
	global $PLUGIN_MANAGER;
	if (! $PLUGIN_MANAGER->plugin_config->value('blogroll', 'creator_output', 0)) {
		$plug = new Blogroll();
	}
}