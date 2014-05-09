<?php
class TinyMCEEditor extends Plugin {

	public function __construct() {
		$this->plugin_desc = _("Use TinyMCE for the post editor");
		$this->plugin_version = "0.1.0";
		$this->addOption("theme", _("TinyMCE theme to use"),"advanced","select",
			array("basic"=>_("Basic"),"advanced"=>_("Advanced"))
			);
		$this->addOption("url", _('URL to TinyMCE'), '//tinymce.cachefly.net/4.0/tinymce.min.js');
		parent::__construct();
	}
	
	protected function getInitString() {
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
				   content_css: "css/content.css",
				   toolbar: "bold italic underline | style-code | forecolor backcolor | link image media fullpage emoticons | bullist numlist | preview",
				   removed_menuitems: "newdocument"
				';
				break;
			default:
				$ret = '';
		}
		
		$obj = '{selector: "textarea#body"';
		if ($ret) {
			$obj .= ", $ret";
		}
		$obj .= "}";
		
		return $obj;
	}
	
	public function show_editor(&$param) {
		Page::instance()->addExternalScript($this->url);
		$init = $this->getInitString();
		$scr  = "jQuery(document).ready(function() {
			var \$input_mode = $('#input_mode');
			
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
			
			if (\$input_mode.val() == ".MARKUP_HTML.") {
				tinymce.init($init);
			}
			\$input_mode.on('change.editor', function(e) {
				var mode = $(this).val();
				if (mode == ".MARKUP_HTML.") { // HTML mode
					tinymce.init($init);
				} else {
					tinymce.remove();
				}
			});
		});";
		//$scr .= $this->getInitString($this->theme);
		Page::instance()->addInlineScript($scr);
	}
	
	public function set_markup(&$blog) {
		$blog->default_markup = MARKUP_HTML;
	}
	
}

$plug = new TinyMCEEditor();
$plug->registerEventHandler("posteditor", "ShowControls", "show_editor");
$plug->registerEventHandler("blog", "InitComplete", "set_markup");
?>