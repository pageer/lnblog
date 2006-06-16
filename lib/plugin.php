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
/*
 * Class: Plugin
 * Abstract class for plugins.  Plugins can inherit from this class to get
 * default configuration functionality.  They can also override the 
 * configuration methods to do custom configuration.
 * Inherits:
 * <LnBlogObject>
 */

require_once("lib/lnblogobject.php");
require_once("lib/creators.php");

class Plugin extends LnBlogObject{

	/*	Property: plugin_desc
	A short description of the plugin. */
	var $plugin_desc;
	/* Property: plugin_version
	The version number of the plugin.  This should be in "1.2.3" format. */
	var $plugin_version;
	/* Property: member_list
	An associative array of arrays, with the form member=>settings, where 
	member is the name of a member variable of your class and settings is an 
	associative array of the configuration data for that member.  This array 
	is used by the default configuration show/save methods. 
	
	The following is a list of possible configuration settings for a member
	variable.  If a setting is not given for a particular control, then the
	default value will be used.  
	
	description - A descriptive string for the variable.  This will be 
	              displayed on the configuration screen when the user modifies
	              the setting for this variable.  This element is *required*.
	control     - The type of control used to display this variable on the 
	              configuration screen.  For the most part, these map directly
	              to HTML input element types.  The currently recognized 
	              values are "text", "checkbox", "radio", and "select".  
	              The *default* is "text".
	default     - The default value for this variable.  This value will be 
	              used if the user does not specify a setting.  Also, if the
	              user modifies other settings, no configuration entry will
	              be saved for this variable if the value is still the 
	              default.  This is important because of pre-blog overriding.
	              The default is the empty string.
	options     - An array of the form value=>description, where the value
	              keys are control values and the descriptions describe each
	              choice for the user.  These are used only for radio button
	              and selection box controls, with each array element 
	              representing an option for the user to select .
	*/
	var $member_list;

	/* Constructor:
	Insert initialization code into the constructor.  You MUST OVERRIDE
	the constructor for your concrete subclass (i.e. you must have an
	explicit constructor). */

	function Plugin() {
		$this->plugin_desc = "Abstract plugin.";
		$this->plugin_version = "0.0.0";
		$this->member_list = array();
		if ($this->member_list) $this->getConfig();
	}

	/* Method: addOption
	A short-hand way to add configuration options.  Adds the necessary 
	values to member_list all in one shot.

	Parameters:
	name        - The name of the option.
	description - A short description for the user to see.
	default     - The default value.
	control     - Optional control to use.  The default is "text".
	options     - An array of options for radio and select controls.
	*/

	function addOption($name, $description, $default, $control="text", $options=false) {
		if (! isset($this->member_list)) $this->member_list = array();
		$this->$name = $default;
		$this->member_list[$name] = 
			array("description"=>$description, 
			      "default"=>$default, "control"=>$control);
		if ($options) $this->member_list[$name]["options"] = $options;
	}

	/*
	Method: showConfig
	Displays the plugin configuration in an HTML form.  You *must* make sure
	to initialize the member_list for this to work.

	Parameters:
	page - A reference to the page which will display the configuration.  
	       This is useful for configs that need to add linked-in stylesheets
	       or external Javascript files.

	Returns:
	*Optionally* returns the form markup as a string.
	*/

	function showConfig(&$page) {
		if (! $this->member_list) return false;
		
		echo "<fieldset>\n";
		echo '<form method="post" ';
		echo 'action="'.current_uri(true).'" ';
		echo "id=\"plugin_config\">\n";
		
		foreach ($this->member_list as $mem=>$config) {
			if (! isset($config["control"])) $config["control"] = "text";
			if ($config["control"] == "checkbox") { 
				echo '<div>';
				echo '<label for="'.$mem.'">'.$config["description"].'</label>';
				echo '<input name="'.$mem.'" id="'.$mem.'" type="checkbox"';
				if ($this->$mem) echo 'checked="checked" ';
				echo " /></div>\n";
			} elseif ($config["control"] == "radio") {
				echo '<fieldset style="margin: 1%; padding: 1%">';
				echo '<legend>'.$config["description"].'</legend>';
				foreach ($config["options"] as $val=>$desc) {
					echo '<label for="'.$val.'">'.$desc.'</label>';
					echo '<input name="'.$mem.'" id="'.$val.'" type="radio" value="'.$val.'"';
					if ($this->$mem == $val) echo 'checked="checked"';
					echo ' /><br />';
				}
				echo "</fieldset>\n";
			} elseif ($config["control"] == "select") {
				echo '<div>';
				echo '<label for="'.$mem.'">'.$config["description"]."</label>\n";
				echo '<select name="'.$mem.'" id="'.$mem."\">\n";
				foreach ($config["options"] as $val=>$desc) {
					echo '<option value="'.$val.'"';
					if ($this->$mem == $val) echo ' selected="selected"';
					echo '>'.$desc."</option>\n";
				}
				echo "</select>\n</div>\n";
			} else { 
				echo '<div>';
				echo '<label for="'.$mem.'">'.$config["description"].'</label>';
				echo '<input name="'.$mem.'" id="'.$mem.'" type="text" value="'.$this->$mem.'"';
				echo " /></div>\n";
			}
		}
		
		echo "<div>\n";
		echo '<input type="hidden" name="plugin" id="plugin" value="'.get_class($this).'" />';
		echo '<input type="submit" value="Submit" />';
		echo '<input type="reset" value="Clear" />'."\n";
		echo "</div>\n";
		echo "</form>\n";
		echo "</fieldset>\n";
		return false;
	}

	# Method: updateConfig
	# Retrieves configuration data for the plugin from an HTTP POST and
	# stores the data in the relevant files.  
	#
	# Returns: 
	# True on success, false on failure.
	
	function updateConfig() {
		if (! $this->member_list) return false;
		if (defined("BLOG_ROOT")) {
			$parser = NewINIParser(BLOG_ROOT.PATH_DELIM."plugins.ini");
		} else {
			$parser = NewINIParser(USER_DATA_PATH.PATH_DELIM."plugins.ini");
		}
		foreach ($this->member_list as $mem=>$config) {
			if (isset($config["control"]) && $config["control"] == "checkbox") {
				$this->$mem = (POST($mem) ? "1":"0");
			} else {
				$this->$mem = POST($mem);
			}
			# Only record the setting if the value is not the default.
			# We set the value if there is no default or if the value is
			# currently the default AND a configuration value has not 
			# been set.  
			# In other words, if it's at the default and has never been set,
			# then don't do anything.
			if ( (! isset($config["default"]) && $this->$mem == "") || 
			     ( $this->$mem != $config["default"] && 
			       ! $parser->valueIsSet(get_class($this), $mem) ) || 
			       $parser->valueIsSet(get_class($this), $mem) ) {
				$parser->setValue(get_class($this), $mem, $this->$mem);
			}
		}
		$ret = $parser->writeFile();
		return $ret;
	}
	
	# Metod: getConfig
	# Reads the configuration data for the plugin from a file and stores it
	# in class variables.  
	
	function getConfig() {
		global $PLUGIN_MANAGER;
		$parser =& $PLUGIN_MANAGER->plugin_config;
		foreach ($this->member_list as $mem=>$config) {
			$this->$mem = (isset($config["default"]) ? $config["default"] : "");
			$val = $parser->value(get_class($this), $mem, $this->$mem);
			if ($val == "1" || $val == "0") $this->$mem = intval($val);
			else $this->$mem = $val;
		}
	}
	
}

?>
