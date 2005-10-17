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

define("TOKEN_TYPE_OPENTAG", 0);
define("TOKEN_TYPE_CLOSETAG", 1);
define("TOKEN_TYPE_TEXT", 2);
define("TOKEN_TYPE_LINEBREAK", 3);
define("TOKEN_TYPE_PARABREAK", 4);

class LBToken {

	var $type;
	var $text;
	var $attrib;

	function LBToken($type=false, $text=false, $attrib=false) {
		$this->type = $type;
		$this->text = $text;
		$this->attrib = $attrib;
	}

}

/* A tokenizer for LBCode.  The types of tokens are as follows:
   1) Open tag
   2) Close tag
   3) Text
	4) Line break
	5) Paragraph break
*/

class Tokenizer {

	var $data;

	function Tokenizer($file=false) {
		if ($file && file_exists($file)) {
			if (function_exists("file_get_contents")) {
				$this->data = file_get_contents($file);
			} else {
				$this->data = implode("", file($file));
			}
		} elseif ($file) {
			$this->data = $file;
		}
	}

	function get() {

	}

}

?>
