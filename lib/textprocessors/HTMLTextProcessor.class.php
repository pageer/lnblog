<?php
class HTMLTextProcessor extends TextProcessor {
	public $filter_id = MARKUP_HTML;
	
	protected function fixURI($args) {
		if (count($args) == 3) {
			$uri = $args[2];
		} else {
			$uri = $args[1];
		}
		$uri = $this->fixIndividualURI($uri);
		if (count($args) == 3) {
			return 'src="'.$uri.'"';
		} else {
			return 'href="'.$uri.'"';
		}
	}
	
	public function toHTML() {
		if ($this->entry) {
			$this->formatted = preg_replace_callback("/src=['\"]([^\:]+)['\"]/",
													 array($this, 'fixURI'), $this->text);
			$this->formatted = preg_replace_callback("/href=['\"]([^\:@]+)['\"]/", 
													 array($this, 'fixURI'), $this->formatted);
		}
	}
}