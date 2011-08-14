<?php
class LBCodeTextProcessor extends TextProcessor {
	public $filter_id = MARKUP_BBCODE;
	public $filter_name = 'LBCode';
	public $strip = false;
	
	protected function fixURI($args) {
		if (count($args) == 3) {
			$uri = $args[2];
		} else {
			$uri = $args[1];
		}
		$uri = $this->fixIndividualURI($uri);
		if (count($args) == 3) {
			return '[img'.$args[1].'='.$uri.']';
		} else {
			return '[url='.$uri.']';
		}
	}
	
	protected function toHTML() {
		if ($this->entry) {
			$this->formatted = preg_replace_callback("/\[img(-?\w+)?=([^\]\:]+)\]/",
													 array($this, 'fixURI'), $this->formatted);
			$this->formatted = preg_replace_callback("/\[url=([^\:@\]]+)\]/", 
													 array($this, 'fixURI'), $this->formatted);
		}
		$this->formatted = $this->sanitizeText($this->formatted);
		
		$patterns[0] = "/\[url=(\S+)\](.+)\[\/url\]/Usi";
		$patterns[1] = "/\[img=(.+)](.+)\[\/img\]/Usi";
		$patterns[2] = "/\[ab=(.+)\](.+)\[\/ab\]/Usi";
		$patterns[3] = "/\[ac=(.+)\](.+)\[\/ac\]/Usi";
		$patterns[4] = "/\[quote\](.+)\[\/quote\]/Usi";
		$patterns[5] = "/\[quote=(.+)\](.+)\[\/quote\]/Usi";
		$patterns[6] = "/\[b\](.+)\[\/b\]/Usi";
		$patterns[7] = "/\[i\](.+)\[\/i\]/Usi";
		$patterns[8] = "/\[u\](.+)\[\/u\]/Usi";
		$patterns[9] = '/\[q\](.+)\[\/q\]/Usi';
		$patterns[10] = '/\[q=(.+)\](.+)\[\/q\]/Usi';
		$patterns[11] = "/(\r?\n\s*)?\[list\]\s*\r?\n(.+)\[\/list\](\s*\r?\n)?/si";
		$patterns[12] = "/(\r?\n\s*)?\[numlist\]\s*\r?\n(.+)\[\/numlist\](\s*\r?\n)?/si";
		$patterns[13] = "/\[\*\](.*)\r?\n/Usi";
		$patterns[14] = "/\[code\](.*)\[\/code\]/Usi";
		$patterns[15] = "/\[t\](.*)\[\/t\]/Usi";
		$patterns[16] = "/\[img-left=(.+)\](.+)\[\/img-left\]/Usi";
		$patterns[17] = "/\[img-right=(.+)\](.+)\[\/img-right\]/Usi";
		$patterns[18] = "/\[h\](.*)\[\/h\]/Usi";
		$patterns[19] = "/\[color=(.+)\](.+)\[\/color\]/Usi";

		
		$whitespace_patterns[0] = '/\r\n\r\n/';
		$whitespace_patterns[1] = '/\n\n/';
		$whitespace_patterns[2] = '/\r\n/';
		$whitespace_patterns[3] = '/\n/';
		$whitespace_patterns[4] = '/\t/';
		
		/*
		$code_patterns[0] = '/(<code.*)<\/p><p>(.*<\/code>)/U';
		$code_patterns[1] = '/(<code.*)<br \/>(.*<\/code>)/U';
		$code_replacements[0] = '$1'."\n\n".'$2';
		$code_replacements[1] = '$1'."\n".'$2';
		*/
		
		if ($this->strip) {
			$replacements[0] = '$2';
			$replacements[1] = '';
			$replacements[2] = '$2';
			$replacements[3] = '$2';
			$replacements[4] = '$1';
			$replacements[5] = '$2';
			$replacements[6] = '$1';
			$replacements[7] = '$1';
			$replacements[8] = '$1';
			$replacements[9] = '$1';
			$replacements[10] = '$2';
			$replacements[11] = '$2';
			$replacements[12] = '$2';
			$replacements[13] = '$1';
			$replacements[14] = '$1';
			$replacements[15] = '$1';
			$replacements[16] = '';
			$replacements[17] = '';
			$replacements[18] = '$1';
			$replacements[19] = '$2';
			
			$whitespace_replacements[0] = "\r\n\r\n";
			$whitespace_replacements[1] = "\n\n";
			$whitespace_replacements[2] = "\r\n";
			$whitespace_replacements[3] = "\n";
			$whitespace_replacements[4] = "\t";
			
			
		} else {
			$replacements[0] = '<a href="$1">$2</a>';
			$replacements[1] = '<img alt="$2" title="$2" src="$1" />';
			$replacements[2] = '<abbr title="$1">$2</abbr>';
			$replacements[3] = '<acronym title="$1">$2</acronym>';
			$replacements[4] = '</p><blockquote><p>$1</p></blockquote><p>';
			$replacements[5] = '</p><blockquote cite="$1"><p>$2</p></blockquote><p>';
			$replacements[6] = '<strong>$1</strong>';
			$replacements[7] = '<em>$1</em>';
			$replacements[8] = '<span style="text-decoration: underline;">$1</span>';
			$replacements[9] = '<q>$1</q>';
			$replacements[10] = '<q cite="$1">$2</q>';
			$replacements[11] = '</p><ul>$2</ul><p>';
			$replacements[12] = '</p><ol>$2</ol><p>';
			$replacements[13] = '<li>$1</li>';
			$replacements[14] = '<code>$1</code>';
			$replacements[15] = '<tt>$1</tt>';
			$replacements[16] = '<img alt="$2" title="$2" style="float: left; clear: none;" src="$1" />';
			$replacements[17] = '<img alt="$2" title="$2" style="float: right; clear: none;" src="$1" />';
			$replacements[18] = '</p><h'.LBCODE_HEADER_WEIGHT.'>$1</h'.LBCODE_HEADER_WEIGHT.'><p>';
			$replacements[19] = '<span style="color: $1">$2</span>';
			
			$whitespace_replacements[0] = '</p><p>';
			$whitespace_replacements[1] = '</p><p>';
			$whitespace_replacements[2] = '<br />';
			$whitespace_replacements[3] = '<br />';
			$whitespace_replacements[4] = '&nbsp;&nbsp;&nbsp;';
			
		}
		
		ksort($patterns);
		ksort($replacements);
		$this->formatted = preg_replace($patterns, $replacements, $this->formatted);
		$this->formatted = preg_replace($whitespace_patterns, $whitespace_replacements, $this->formatted);
		
		if (! $this->strip) {
			$this->formatted = "<p>".$this->formatted."</p>";
			# Strip out extraneous empty paragraphs.
			$this->formatted = preg_replace('/<p><\/p>/', '', $this->formatted);
		}
	}
	
	
}