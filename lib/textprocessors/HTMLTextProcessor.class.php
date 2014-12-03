<?php
class HTMLTextProcessor extends TextProcessor {
	public $filter_id = MARKUP_HTML;
	public $filter_name = 'HTML';
	
	protected function fixURI($args) {
		$uri = $this->fixIndividualURI($args[2]);
		return $args[1] . '="' . $uri . '"';
	}
	
	protected function fixAllUrls($text) {
		$ret = preg_replace_callback("/(src)=['\"]([^\:]+)['\"]/U",
									 array($this, 'fixURI'), $text);
		$ret = preg_replace_callback("/(href)=['\"]([^\:@]+)['\"]/U", 
									 array($this, 'fixURI'), $ret);
		return $ret;
	}
	
	public function toHTML() {
		if ($this->entry) {
			$this->formatted = $this->fixAllUrls($this->text);
		}
	}
}