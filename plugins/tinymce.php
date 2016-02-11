<?php
# Plugin: TinyMCEEditor
# This plugin provides a WYSIWYG (What You See Is What You Get) editor widget for typing your posts.
# It uses <TinyMCE at http://www.tinymce.com/> as the editor control.
#
# The plugin currently provides two "theme" options for TinyMCE.  The default is "Advanced", which provides
# a fairly full-featured editor experience, with most of the options and controls you're likely to need.  The
# "Basic" theme is a much simpler, more stripped-down version for those who don't like a lot of clutter in
# their editor's tool bar.
#
# The other option for this plugin is the URL to the TinyMCE instance to use.  Unless you want to host your own
# copy of TinyMCE, you can ignore this.  The default value is the CDN version of TinyMCE 4.0 hosted by CacheFly.
# This is free for public use and is probably much faster than your server anyway, so it will be the right
# choice for most people.

class TinyMCEEditor extends Plugin {
	
	protected $file_extensions = array('htm', 'html');

	public function __construct() {
		$this->plugin_desc = _("Use TinyMCE for the post editor and file editor.");
		$this->plugin_version = "0.2.1";
		$this->addOption("theme", _("TinyMCE theme to use"),"advanced","select",
			array("basic"=>_("Basic"),"advanced"=>_("Advanced"))
			);
		$this->addOption("url", _('URL to TinyMCE'), '//tinymce.cachefly.net/4.0/tinymce.min.js');
		parent::__construct();
	}
	
	protected function getInitString($selector) {
		$selector = $selector ?: 'textarea#body';
		switch ($this->theme) {
			case "basic":
				$ret = '';
				break;
			case "advanced":
				$ret = '
					theme: "modern",
					plugins: [
						 "link image searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
						 "hr charmap table contextmenu directionality emoticons template paste textcolor preview stylebuttons"
				   ],
				   browser_spellcheck: true,
				   gecko_spellcheck: true,
				   content_css: "css/content.css",
				   toolbar: "bold italic underline | style-code | forecolor backcolor | link image media fullpage emoticons | bullist numlist | preview",
				   removed_menuitems: "newdocument"
				';
				break;
			default:
				$ret = '';
		}
		
		$obj = '{
			selector: "'.$selector.'",
			setup: function(ed) {
				ed.on("change", function(e) {
					current_text_content = ed.getContent();
				});
			}';
		if ($ret) {
			$obj .= ", $ret";
		}
		$obj .= "}";
		
		return $obj;
	}
	
	public function show_editor($selector = '') {
		Page::instance()->addExternalScript($this->url);
		$init = $this->getInitString($selector);
		$scr  = "jQuery(document).ready(function() {
			var \$input_mode = $('#input_mode'),
			    unconditional_display = ".($selector ? 'true' : 'false').";
			
			// Suppress the plugin on the list-link page.
			if (window.location.href.match('[?&]list=yes')) {
				return;
			}
			
			// Style buttons plugin from http://blog.ionelmc.ro/2013/10/17/tinymce-formatting-toolbar-buttons/
			tinyMCE.PluginManager.add('stylebuttons', function(editor, url) {
				['pre', 'p', 'code', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].forEach(function(name){
					editor.addButton('style-' + name, {
						tooltip: 'Toggle ' + name,
						text: name.toUpperCase(),
						onClick: function() { editor.execCommand('mceToggleFormat', false, name); },
						onPostRender: function() {
							var self = this, setup = function() {
								editor.formatter.formatChanged(name, function(state) {
									self.active(state);
								});
							};
							editor.formatter ? setup() : editor.on('init', setup);
						}
					})
				});
			});
			
			setInterval(function tinymceUpdate() {
				current_text_content = tinyMCE.activeEditor.getContent();
			}, 5000);
			
			if (unconditional_display) {
				tinymce.init($init);
				$('#postform').addClass('rich-text');
				
				var \$toggle_button = $('<button>"._('Toggle HTML Editor')."</button>');
				\$toggle_button.off('click').on('click', function (e) {
					e.preventDefault();
					if (tinymce.editors.length == 0) {
						tinymce.init($init);
					} else {
						tinymce.remove();
					}
					return false;
				});
				
				$('textarea').closest('form')
							  .find('button, input[type=submit], input[type=reset]')
							  .filter(':last')
							  .after(\$toggle_button);
			} else {
				if (\$input_mode.val() == ".MARKUP_HTML.") {
					tinymce.init($init);
					$('#postform').addClass('rich-text');
				}
				\$input_mode.on('change.editor', function(e) {
					var mode = $(this).val();
					$('#postform').toggleClass('rich-text', mode == ".MARKUP_HTML.");
					if (mode == ".MARKUP_HTML.") { // HTML mode
						tinymce.init($init);
					} else {
						tinymce.remove();
					}
				});
			}
		});";
		Page::instance()->addInlineScript($scr);
	}
	
	public function set_markup(&$blog) {
		$blog->default_markup = MARKUP_HTML;
	}
	
	public function file_editor() {
		$file_ext = pathinfo(GET('file'), PATHINFO_EXTENSION);
		$editor = GET('richedit');
		if (in_array($file_ext, $this->file_extensions) && $editor != 'false') {
			$this->show_editor('textarea#output');
		}
	}
}

$plug = new TinyMCEEditor();
$plug->registerEventHandler("posteditor", "ShowControls", "show_editor");
$plug->registerEventHandler("blog", "InitComplete", "set_markup");
$plug->registerEventHandler('page', 'FileEditorReady', 'file_editor');
