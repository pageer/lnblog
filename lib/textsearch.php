<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005 Peter A. Geer <pageer@skepticats.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

define("TEXT_SEARCH_CONTEXT_CHARS", 30);
define("TEXT_SEARCH_TERM_SEPARATOR", "|");

class TextSearch {
	
	var $text;
	var $search_string;
	var $context;
	
	function TextSearch($txt=false, $searchstr=false) {
		$this->context = TEXT_SEARCH_CONTEXT_CHARS;
		if ($txt) $this->text = $txt;
		else $this->text = false;
		if ($searchstr) $this->search_string = $searchstr;
		else $this->search_string = false;
	}

	function parseSearchTerms() {
	
		# Strip any extra spaces
		$str = preg_replace('/ +/', trim($this->search_string));
		
		# Divide up the string, keeping quoted sections together.
		$hit_quote = false;
		$search_str = "";
		for ($i=0; $i < strlen($str); $i++) {
			if ($str{$i} == '"') {
				if ($hit_quote) $hit_quote = false;
				else $hit_quote = true;
			} elseif ($str{$i} == " ") {
				$search_str .= TEXT_SEARCH_TERM_SEPARATOR;
			} else {
				$search_str .=  $str{$i};
			}
		}
		
		$match_arr = explode(TEXT_SEARCH_TERM_SEPARATOR, $search_str);
		$ret = array();
		foreach ($match_arr as $term) {
			$ret[] = trim($term);
		}
		return $ret;
	}

	function search($str=false) {
		
	}
	
}
