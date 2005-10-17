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

require_once("lib/plugin.php");

/* Class: PluginManager
 * A class to load and manage plugins.
 */

class PluginManager {

	var $config_file;
	var $plugin_list;
	var $plugin_config;

	function PluginManager($config=false) {
		if ($config) {
			$config_file = $config;
			#$plugin_config = NewINIParser($this->config_file);
		}
	}

	/* Method: getPluginList
	 * List all the classes that are descended from Plugin.
	 *
	 * Returns:
	 * An array of class names.
	 */

	function getPluginList() {
		$classes = get_declared_classes();
		$plugin_classes = array();
		foreach ($classes as $cls) {
			$obj = new $cls;
			if (is_subclass_of($obj, "Plugin")) $plugin_classes[] = $cls;
		}
		return $plugin_classes;
	}

	function getClassNameFromFile($filename) {
		$us_pos = strrpos($filename, "-");
		if ($us_pos === false) $us_pos = 0;
		else $us_pos += 1;
		$dot_pos = strrpos($filename, ".");
		if ($dot_pos === false) $dot_pos = strlen($filename) - 1;
		return substr($filename, $us_pos, $dot_pos - $us_pos);
	}

	/* Method: loadPlugins
	 * Load all plugins.  In particular, include()s every file in the plugins
	 * directory then ends with a .php suffix in alphabetical order.  Plugins
	 * are found in the "plugins" directory under BLOG_ROOT or INSTALL_ROOT.
	 * Plugins in BLOG_ROOT take precedence.  Subdirectories are not scanned.
	 */

	function loadPlugins() {
		$plugin_dir_list = array();
		if (defined("BLOG_ROOT")) 
			$plugin_dir_list[] = BLOG_ROOT.PATH_DELIM."plugins";
		$plugin_dir_list[] = INSTALL_ROOT.PATH_DELIM."plugins";
		$file_list = array();
		foreach ($plugin_dir_list as $dir) {
			if (! is_dir($dir)) continue;
			$dirhand = opendir($dir);
			$dir_list = array();
			while ( false !== ($ent = readdir($dirhand)) ) {
				if (is_file($dir.PATH_DELIM.$ent) && 
				    strtolower(substr($ent, strrpos($ent,"."))) == ".php") {
					$file_list[] = $ent;
				}

			}
			closedir($dirhand);		
		}
		$file_list = array_unique($file_list);
		sort($file_list);
		foreach ($file_list as $f) {
			include("plugins/".$f);
		}
	}
	
}

$PLUGIN_MANAGER = new PluginManager();
$PLUGIN_MANAGER->loadPlugins();
?>
