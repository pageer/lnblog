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

define("TOKEN_OPENTAG", 0);
define("TOKEN_CLOSETAG", 1);
define("TOKEN_TEXT", 2);
define("TOKEN_LBREAK", 3);
define("TOKEN_PBREAK", 4);

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

/* A scanner/tokenizer for LBCode.  The types of tokens are as follows:
   1) Open tag
   2) Close tag
   3) Text
	4) Line break
	5) Paragraph break
*/

class Tokenizer {

	var $data;
	var $pos;

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
		$this->pos = 0;
	}

	# Method: getch
	# Gets the next character in the input streatm.
	# This also increments the internal position pointer.
	#
	# Returns:
	# A single-character string.
	function getch() {
		return $this->data[$this->pos++];
	}

	# Method: ungetch
	# Go backwards in the input buffer.
	# This resets the internal input pointer to a previously visited value.
	#
	# Parameters:
	# num - The number of characters to unget.  *Default* is 1.
	function ungetch($num=1) {
		$this->pos = $this->pos - $num;
	}

	# Method: skip
	# Skips all input of a specified type.
	

	# Method: seek
	# Gets characters until it finds a certain input.
	# If the target is found, the internal pointer is adjusted.
	# If not, the pointer is reset to the starting poing.
	#
	# Parameters:
	# target      - The ending characters it's looking for.  May be a single 
	#               character or an array.
	# to_line_end - *Optional* parameter that determines how far to seek.
	#               The *default* is true, meaning that it will stop seeking 
	#               when it hits an end of line.  When set to false, it will
	#               seek through all input.
	#
	# Returns:
	# On success, a string containing all characters from the starting point

	function seek($target, $to_line_end=true) {

	}

	# Method: get
	# Return the next token in the input stream.
	#
	# Returns:
	# An LBToken object for the next token in the input.
	function get() {
		$char = $this->getch();

		# If the token is a newline, check to see if it's doubled.
		if ($char == "\n") {

			# First, skip any whitespace.
			$num_spaces = 0;
			while ( ($char = $this->getch()) == ' ' || $char == "\t" ) 
				$num_spaces++;
				
			# If the character is a newline, 
			if ($char == "\n") return new LBToken(TOKEN_PBREAK);
			else $this->ungetch($num_spaces+1);
			return new LBToken(TOKEN_LBREAK);
		
		# If the token starts a tag, try to get the whole tag.
		} elseif ($char == "[") {
			
			if 
			
		}
	}

}

?>
