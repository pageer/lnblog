<?php
class HTMLTextProcessor extends TextProcessor {
	public $filter_id = MARKUP_HTML;
	public $filter_name = 'HTML';
	
	protected function fixURI($args) {
		$uri = $this->fixIndividualURI($args[2]);
		return $args[1] . '="' . $uri . '"';
	}
	
	public function toHTML() {
		if ($this->entry) {
			$this->formatted = preg_replace_callback("/(src)=['\"]([^\:]+)['\"]/U",
													 array($this, 'fixURI'), $this->formatted);
			$this->formatted = preg_replace_callback("/(href)=['\"]([^\:@]+)['\"]/U", 
													 array($this, 'fixURI'), $this->formatted);
		}
	}
}