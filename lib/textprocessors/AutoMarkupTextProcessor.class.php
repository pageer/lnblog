<?php
class AutoMarkupTextProcessor extends TextProcessor {
	
	public $filter_id = MARKUP_NONE;
	public $use_nofollow = false;
	
	
	public function toHTML() {
		$this->formatted = $this->sanitizeText($this->text);
		
		$patterns[0] = "/((http|https|ftp):\/\/\S*)/i";
		$patterns[1] = '/\r\n(\r\n)+/';
		$patterns[2] = '/\n\n/';
		$patterns[3] = '/\n/';
		if ($this->use_nofollow) {
			$replacements[0] = '<a href="$1" rel="nofollow">$1</a>';
		} else {
			$replacements[0] = '<a href="$1">$1</a>';
		}
		$replacements[1] = '</p><p>';
		$replacements[2] = '</p><p>';
		$replacements[3] = '<br />';
		ksort($patterns);
		ksort($replacements);
		$this->formatted = preg_replace($patterns, $replacements, $this->formatted);
		$this->formatted = "<p>".$this->formatted."</p>";
	}
	
}