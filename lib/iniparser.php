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

# This is a simple class to parse INI files.

define("INIPARSER_CASE_SENSITIVE", true);

class INIParser {
	
	var $filename;
	var $current_section;
	var $case_insensitive;
	var $data;
	
	function INIParser ($file=false) {
		$this->current_section = false;
		$this->case_insensitive = INIPARSER_CASE_SENSITIVE;
		if ( $file && file_exists($file) ) {
			$this->filename = $file;
			$this->readFile();
		} else {
			$this->filename = false;
		}
	}

	# Read the contents of an INI file into a two-dimensional array.

	function readFile() {
		$file_content = file($this->filename);
		$this->data = array();
		$cur_sec = false;
		foreach ($file_content as $line) {
			$line = trim($line);
			if ( preg_match("/^\[.+\]$/",$line) ) {
				$sec_name = str_replace(array("[", "]"), "", $line);
				# If this section already exists, ignore the line.
				if (! isset($this->data[$sec_name]) ) {
					$this->data[$sec_name] = array();
					$cur_sec = $sec_name;
				}
			} else {
				$line_arr = explode('=', $line, 2);
				# If the line doesn't match the var=value pattern, or if it's a
				# comment then add it without a key.
				if (isset($line_arr[1]) && 
				    ! ( substr(trim($line_arr[0]), 0, 1) == '#' || 
					     substr(trim($line_arr[0]), 0, 1) == ';') ) {
					$this->data[$cur_sec][$line_arr[0]] = $line_arr[1];
				} else {
					$this->data[] = $line_arr[0];
				}
			}
		}
	}

	# Return the INI file structure as a string that can be written to a file.

	function getFile() {
		$ret = "";
		foreach ($this->data as $sec=>$vals) {
			if (is_array($vals)) {
				$ret .= "[".$sec."]\n";
				foreach ($vals as $key=>$value) {
					$ret .= $key.'='.$value."\n";
				}
			} else {
				$ret .= $vals."\n";
			}
		}
		return $ret;
	}

	# Write the INI file back to the disk.

	function writeFile($file=false) {
		if ($file) $this->filename;
		if (! $this->filename) return false;
		return write_file($this->filename, $this->getFile());
	}

	function value($sec, $var=false, $default=false) {
		if ($var && isset($this->data[$sec]) && 
		    isset($this->data[$sec][$var]) ) {
			$this->current_section = $sec;
			return $this->data[$sec][$var];
		} elseif (!$var && isset($this->data[$this->current_section]) && 
		          isset($this->data[$this->current_section][$sec]) ) {
			return $this->data[$this->current_section][$sec];
		} else {
			return $default;
		}
	}

	function valueIsSet($sec, $var=false) {
		if ($var && isset($this->data[$sec]) ) {
			$this->current_section = $sec;
			return isset($this->data[$sec][$val]);
		} elseif (!$var && isset($this->data[$this->current_section]) ) { 
			return isset($this->data[$this->current_section][$sec]);
		} else {
			return false;
		}
	}

	# Set a value.  If the last parameter is not given, the meanings are moved
	# back one.  This is  a hack because PHP 4 doesn't have function overloading.

	function setValue ($sec, $var, $val=false) {
		if ($val) {
			if (isset($this->data[$sec]) ) {
				$this->data[$sec][$var] = $val;
			} else {
				$this->data[$sec] = array($var=>$val);
			}
			return $this->data[$sec][$var];
		} else {
			if (isset($this->data[$this->current_section]) ) {
				$this->data[$this->current_section][$sec] = $var;
			} else {
				$this->data[$this->current_section] = array($sec=>$var);
			}
			return $this->data[$this->current_section][$sec];
		}
	}

}

?>
