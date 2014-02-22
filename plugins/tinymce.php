<?php
class TinyMCEEditor extends Plugin {

	public function __construct() {
		$this->plugin_desc = _("Use TinyMCE for the post editor");
		$this->plugin_version = "0.1.0";
		$this->addOption("theme", _("TinyMCE theme to use"),"advanced","select",
			array("basic"=>_("Basic"),"advanced"=>_("Advanced"))
			);
		$this->addOption("url", _('URL to TinyMCE'), '//tinymce.cachefly.net/4.0/tinymce.min.js');
		$this->getConfig();
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
						 "hr charmap table contextmenu directionality emoticons template paste textcolor preview"
				   ],
				   content_css: "css/content.css",
				   toolbar: "bold italic underline | forecolor backcolor | link image media fullpage emoticons | bullist numlist | preview",
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