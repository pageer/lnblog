<?php

require_once('lib/xml.php');
class AuthorBio extends Plugin {
	
	public $title;
	public $picture_url;
	public $bio;
	
	public function __construct($do_output=false) {
		$this->plugin_version = '0.1.0';
		$this->plugin_desc = _('Display information about the author in the sidebar.');
		$this->addOption('title', _('Sidebar box title'), _('About Me'));
		$this->addOption('picture_url', _('URL of your profile photo'), '');
		$this->addOption('bio', _('Bio to display'), '', 'textarea');
		
		$options = TextProcessor::getFilterList();
		$this->addOption('markup_type', 'Markup format', MARKUP_HTML, 'select', $options);
		
		$this->addOption('no_event',
			_('No event handlers - do output when plugin is created'),
			System::instance()->sys_ini->value("plugins","EventDefaultOff", 0), 
			'checkbox');
		
		$this->getConfig();

		if ( $this->no_event || 
		     System::instance()->sys_ini->value("plugins","EventForceOff", 0) ) {
			# If either of these is true, then don't set the event handler
			# and rely on explicit invocation for output.
		} else {
			$this->registerEventHandler("sidebar", "OnOutput", "output");
		}
		
		if ($do_output) $this->output();
	}
	
	public function output() {
		$tpl = NewTemplate('sidebar_panel_tpl.php');
		
		$tpl->set('PANEL_TITLE', $this->title);
		
		ob_start();
		?>
		<?php if ($this->picture_url): ?>
		<div>
			<img src="<?php echo $this->picture_url;?>" alt="" />
		</div>
		<?php endif; ?>
		<div>
			<?php echo TextProcessor::get($this->markup_type, null, $this->bio)->getHTML();?>
		</div>
		<?php
		$content = ob_get_clean();
		$tpl->set('PANEL_CLASS', 'panel');
		$tpl->set('PANEL_CONTENT', $content);
		echo $tpl->process();
	}
}

if (! PluginManager::instance()->plugin_config->value('author_bio', 'creator_output', 0)) {
	$plug = new AuthorBio();
}
