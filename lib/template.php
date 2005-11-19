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
# Class: PHPTemplate
# A PHP-based template class inspired by the example at
# <http://www.sitepoint.com/print/beyond-template-engine>
# The idea is to simplify the template engine by just using regular old 
# PHP source files as template files.  Since the syntax for advanced 
# templating is not significantly less complicated than regular PHP
# syntax, we might as well save ourselves some trouble and just use PHP.
#
# Please note that this class will depend on an appropriately set 
# include_path.  This will allow us to transparently have different templates
# for different sections of the site and fall back to the default if we
# don't want to bother.
#
# Ihnerits:
# <LnBlogObject>

require_once("lib/lnblogobject.php");

class PHPTemplate extends LnBlogObject {

	var $file;   # The name of the template file to use.
	var $vars;   # An array of variables to register in the template.

	function PHPTemplate($file="") {
		$this->file = $file;
		$this->vars = array();
	}

	# Method: set
	# Sets a template variable.
	#
	# Parameters:
	# var - The name of the variable.
	# val - *Optional* value for the variable.  *Defaults* to true.
	#
	# Returns:
	# The value passed or true.
	function set($var, $val=true) {
		return $this->vars[$var] = $val;
	}

	# Method: varSet
	# Determine if a template variable has been set.
	#
	# Parameters:
	# var - The name of a template variable.
	# 
	# Returns:
	# True if var has been set, false otherwise.
	function varSet($var) {
		return isset($this->vars[$var]);
	}

	# Method: reset
	# Resets a template back to its empty state, clearing all variables and the file.
	# 
	# Parameters:
	# file - An *optional* file name for the reset template.
	function reset($file="") {
		$this->file = $file;
		$this->vars = array();
	}

	# Method: process
	# Process the template and get the output.
	# 
	# Parameters:
	# return_results - *Optional* boolean that determines whether the output should be 
	#                  returned in a string instead of sent straight to the client.  
	#                  *Default is true.
	#
	# Returns:
	# A string containing the output if return_results is true.  Otherwise, it returns
	# true on success and false on failure.
	function process($return_results=true) {
		ob_start();
		extract($this->vars, EXTR_OVERWRITE);
		include($this->file);
		if ($return_results) {
			$ret = ob_get_contents();
			ob_end_clean();
			return $ret;
		} else return ob_end_flush();
	}

}

?>
