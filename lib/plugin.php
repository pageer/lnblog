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
 */
/*
Inherits:
<LnBlogObject>

1) The plugin class name MUST match the name of the file minus the .php 
   file extension.  This is simply so that the loader can know what class
   to instantiate without grepping through the file.
2) There is a plugins directory where plugins must be installed.  This 
   directory has several subdirectories which serve to segregate the plugin
   "namespace," as it were.  The idea is that particular components will 
	only look in specific directories for their plugins.
3) Each component has a list of plugins which it runs.  The user can use 
   this to define which plugins are loaded and in what order they load.
4) 
*/

require_once("lib/lnblogobject.php");

class Plugin extends LnBlogObject{

	/*	Property: plugin_desc
	A short description of the plugin. */
	var $plugin_desc;
	/* Property: plugin_version
	The version number of the plugin.  This should be in "#.#.#" format. */
	var $plugin_version;
	/* Property: member_list
	An associative array of the form member=>description, where member is 
	the name of a member variable of your class and description is a 
	description of the configuration that member controls.  This array is 
	used by the default configuration show/save methods. */
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

	/*
	Method: showConfig
	Displays the plugin configuration in an HTML form.  You MUST make sure
	to initialize the member_list for this to work.
	The form provided is a list of simple text input fields.  If you wish
	to use a more complicated configuration screen, e.g. with check boxes
	or combo boxes, then you must override this method.

	Parameters:
	page - A reference to the page which will display the configuration.  
	       This is useful for configs that need to add linked-in stylesheets
	       or external Javascript files.

	Returns:
	*Optionally* returns the form markup as a string.
	*/

	function showConfig(&$page) {
		if (! $this->member_list) return false;
?>
<fieldset>
<form method="post" action="<?php echo current_page();?>?plugin=<?php echo get_class($this); ?>" name="plugin_config">
<?php 
		foreach ($this->member_list as $mem=>$desc) {
?> <div><label for="<?php echo $mem; ?>"><?php echo $desc; ?></label>
<input name="<?php echo $mem; ?>" id="<?php echo $mem; ?>" type="text" value="<?php echo $this->$mem; ?>" /></div>
<?php
		}
?>
<div>
<input type="hidden" value="<?php echo get_class($this); ?>" />
<input type="submit" value="Submit" />
<input type="reset" value="Clear" />
</div>
</form>
</fieldset>
<?php
		return false;
	}

	# Retrieves configuration data for the plugin from an HTTP POST and
	# stores the data in the relevant files.  
	# If your plugin allows ANY type of user configuration, then you MUST
	# override this method.  If the plugin doesn't allow any configuration,
	# then you can safely ignore this.
	
	function updateConfig() {
		if (! $this->member_list) return false;
		$parser = NewINIParser(USER_DATA_PATH.PATH_DELIM."plugins.ini");
		foreach ($this->member_list as $mem=>$desc) {
			$this->$mem = POST($mem);
			$parser->setValue(get_class($this), $mem, $this->$mem);
		}
		$parser->writeFile();
	}
	
	# Reads the configuration data for the plugin from a file and stores it
	# in class variables.  
	# If your plugin allows ANY type of user configuration, then you MUST
	# override this method.  If the plugin doesn't allow any configuration,
	# then you can safely ignore this.
	
	function getConfig() {
		$parser = NewINIParser(USER_DATA_PATH.PATH_DELIM."plugins.ini");
		foreach ($this->member_list as $mem=>$desc) {
			$this->$mem = $parser->value(get_class($this), $mem, "");
		}
	}
	
}

?>
