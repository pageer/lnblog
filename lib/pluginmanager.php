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

	var $plugin_list;
	var $exclude_list;
	var $load_first;
	var $load_list;
	var $plugin_config;

	function PluginManager() {
	
		# Merge the global and per-blog config to get a single INI object that
		# all plugins can access.
		$this->getConfig();
		
		$this->plugin_list = $this->getFileList();
		# Get various settings to determine which plugins should be loaded and 
		# in what order.
		$defexcl = "sidebar_archives.php,htaccess_generator.php";
		$excl = $this->plugin_config->value("Plugin_Manager", "exclude_list", $defexcl);
		$this->exclude_list = explode(",", $excl);
		if (! is_array($this->exclude_list)) $this->exclude_list = array();
		
		$deflf = "banner_pageheader.php,menubar_sitemap.php,".
		         "sidebar_loginops.php,sidebar_recent.php,".
		         "sidebar_articles.php,sidebar_calendar.php,".
		         "sidebar_news.php,sidebar_search.php,sidebar_tags.php,".
		         "sidebar_loginlink.php,sidebar_poweredby.php";
		$lf = $this->plugin_config->value("Plugin_Manager", "load_first", $deflf);
		$this->load_first = explode(",", $lf);
		
		$this->load_list = array();
		if (is_array($this->load_first)) {
			foreach ($this->load_first as $val) {
				if ($this->testFile($val)) {
					$this->load_list[$val] = $val;
				}
			}
		}

		foreach ($this->plugin_list as $val) {
			if (! isset($this->load_list[$val])) $this->load_list[$val] = $val;
		}
		
		foreach ($this->exclude_list as $val) {
			if (isset($this->load_list[$val])) {
				$this->load_list[$val] = false;
			}
		}
		
	}

	/* Method: getConfig
	 * Gets configuration data for plugins from INI files.
	 * Merges the installation wide and per-blog settings if applicable.
	 */

	function getConfig() {
		$global_config = NewINIParser(USER_DATA_PATH.PATH_DELIM."plugins.ini");
		$blog_path = get_blog_path();
		if ($blog_path) {
			$blog_config = NewINIParser($blog_path.PATH_DELIM."plugins.ini");
			$blog_config->merge($global_config);
			$this->plugin_config =& $blog_config;
		} else $this->plugin_config =& $global_config;
	}

	/* Method: getPluginList
	 * List all defined classes that are descended from Plugin.
	 * This has the effect of getting a list of loaded plugins.
	 *
	 * Returns:
	 * An array of class names.
	 */

	function getPluginList() {
		$classes = get_declared_classes();
		$plugin_classes = array();
		foreach ($classes as $cls) {
			if ( class_exists($cls) && 
			     in_arrayi("updateconfig", get_class_methods($cls)) &&
			     in_arrayi("showconfig", get_class_methods($cls)) &&
			     in_arrayi("getconfig", get_class_methods($cls)) ) {
				$obj = new $cls;
				if (is_subclass_of($obj, "Plugin")) $plugin_classes[] = $cls;
			}
		}
		return $plugin_classes;
	}

	/* Method: getFileList
	 * List all the plugin files that get loaded.
	 *
	 * Returns:
	 * An array of strings containing filenames.
	 */

	function getFileList() {
		$plugin_dir_list = array();
		$blog_path = get_blog_path();
		if ($blog_path) 
			$plugin_dir_list[] = $blog_path.PATH_DELIM."plugins";
		$plugin_dir_list[] = USER_DATA_PATH.PATH_DELIM."plugins";
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
		return $file_list;
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
		$path = USER_DATA_PATH.PATH_SEPARATOR.INSTALL_ROOT;
		if (get_blog_path()) {
			$path = get_blog_path().PATH_SEPARATOR.$path;
		}
		$old_path = ini_get('include_path');
		ini_set('include_path', $path);
		foreach ($this->load_list as $f=>$v) {
			if ($v) {
				include("plugins/".$f);
			}
		}
		ini_set('include_path', $old_path);
	}
	
	/* Method: testFile
	 * Test if a particular plugin file exists.
	 *
	 * Parameters:
	 * plug - The name of the plugin file.
	 *
	 * Returns:
	 * True if the file exists in the userdata, LnBlog, or blog plugins 
	 * directory, false otherwise.
	 */
	function testFile($plug) {
		$lnblog_path = mkpath(INSTALL_ROOT,'plugins',$plug);
		if (file_exists($lnblog_path)) return true;
		$userdata_path = mkpath(USER_DATA_PATH,'plugins',$plug);
		if (file_exists($userdata_path)) return true;
		$blog = NewBlog();
		if ($blog->isBlog()) {
			$blog_path = mkpath($blog->home_path,'plugins',$plug);
			if (file_exists($blog_path)) return true;
		}
		return false;
	}
	
}

$PLUGIN_MANAGER = new PluginManager();
$PLUGIN_MANAGER->loadPlugins();
?>
