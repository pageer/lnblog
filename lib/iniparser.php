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

# Class: IniParser
# This is a simple class to parse INI files.

class INIParser {
	
	var $filename;
	var $current_section;
	var $case_insensitive;
	var $data;
	
	function INIParser ($file=false) {
		$this->current_section = false;
		$this->case_insensitive = true;
		if ( $file ) $this->filename = $file;
		if ( file_exists($this->filename) ) {
			$this->readFile();
		}
	}
	
	# Method: readFile
	# Read the contents of an INI file into a two-dimensional array.
	# This method preserves comments in the file.

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

	# Method: getFile
	# Serializes the INI file into a string.
	#
	# Returns:
	# The INI file structure as a string that can be written to a file.

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

	# Method: writeFile
	# Write the INI file back to the disk.
	#
	# Parameters:
	# file - The filename to write to.  *Defaults* to the filename property.
	#
	# Returns:
	# True if the write succeeds, false otherwise.

	function writeFile($file=false) {
		if ($file) $this->filename = $file;
		if (! $this->filename) return false;
		return write_file($this->filename, $this->getFile());
	}

	# Method: value
	# Get a value from the INI file.
	#
	# Parameters:
	# Takes up to three parameters.
	# If one parameter is given, then it is interpreted as a key in the 
	# current section (last section accessed.
	# If two are given, the first is a section and the second is a key.
	# If three, then the first is the section, the second is the key, 
	# and the third is a default value if the key is not set.
	#
	# Returns:
	# A string containing the value of the given key, or the default value.

	function value($sec, $var=false, $default=false) {
		if ($var !== false && isset($this->data[$sec]) && 
		    isset($this->data[$sec][$var]) ) {
			$this->current_section = $sec;
			return $this->data[$sec][$var];
		} elseif ($var === false && 
		          isset($this->data[$this->current_section]) && 
		          isset($this->data[$this->current_section][$sec]) ) {
			return $this->data[$this->current_section][$sec];
		} else {
			return $default;
		}
	}

	# Method: valueIsSet
	# Determine if a particular key has been set.
	#
	# Parameters:
	# This can take one or two parameters.  If one is given, it is a key in
	# the current section.  If two, the first is a secion and the second is 
	# a key.
	#
	# Returns:
	# True if the given key has been set, false otherwise.
	
	function valueIsSet($sec, $var=false) {
		if ($var && isset($this->data[$sec]) ) {
			$this->current_section = $sec;
			return isset($this->data[$sec][$var]);
		} elseif (!$var && isset($this->data[$this->current_section]) ) { 
			return isset($this->data[$this->current_section][$sec]);
		} else {
			return false;
		}
	}

	# Method: setValue
	# Sets a value for a key.  
	#
	# Parameters:
	# Takes two or three parameters.  If two, then the first is a key in the 
	# current section and the second is a value.  If three, then the first is
	# a section, the second a key, and the third the value.
	#
	# Returns:
	# The value to which the key was set.
	
	function setValue ($sec, $var, $val=false) {
		if ($val !== false) {
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

	# Method: merge
	# Merge another INI file into the current one.
	# If a key from the other file is not set, it will be set with the value 
	# from the other file.  Keys that are already set will not be modified.
	#
	# Parameters:
	# file - The INIParser object to merge with the current object.

	function merge($file) {
		if ($file->data && ! $this->data) $this->data = $file->data;
		elseif (! $file->data) return;
	
		foreach ($file->data as $sec=>$keys) {
			if (isset($this->data[$sec]) ) {
				foreach ($keys as $key=>$val) {
					if (! isset($this->data[$sec][$key])) {
						$this->data[$sec][$key] = $val;
					}
				}
			} else {
				$this->data[$sec] = $keys;
			}
		}
	}

}

?>
