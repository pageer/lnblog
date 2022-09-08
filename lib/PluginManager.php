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

/* Class: PluginManager
 * A class to load and manage plugins.
 */

class PluginManager
{

    protected static $registry = array();

    var $plugin_list;
    var $disabled;
    var $load_first;
    var $load_list;
    var $plugin_config;
    
    public $default_excluded = array(
        'sidebar_archives.php',
        'htaccess_generator.php',
        'sidebar_googlesearch.php',
    );
    public $default_load_first = array(
        "banner_pageheader.php",
        "menubar_sitemap.php",
        "sidebar_loginops.php",
        "sidebar_recent.php",
        "sidebar_articles.php",
        "sidebar_calendar.php",
        "sidebar_news.php",
        "sidebar_search.php",
        "sidebar_tags.php",
    );
    public $default_load_last = array(
        'sidebar_poweredby.php',
        'sidebar_loginlink.php',
    );
    /**
     * @var array<string, string> $instance_list
     */
    private array $instance_list = [];
    
    static function instance() {
        if (! isset($GLOBALS['PLUGIN_MANAGER'])) {
            $GLOBALS['PLUGIN_MANAGER'] = new PluginManager();
        }
        return $GLOBALS['PLUGIN_MANAGER'];
    }

    public function __construct() {
    
        # Merge the global and per-blog config to get a single INI object that
        # all plugins can access.
        $this->getConfig();
        
        $this->plugin_list = $this->getFileList();
        # Get various settings to determine which plugins should be loaded and 
        # in what order.
        $defaults = implode(',', $this->default_excluded);
        $excl = $this->plugin_config->value("Plugin_Manager", "exclude_list", $defaults);
        
        $this->disabled = explode(",", $excl);
        if (! is_array($this->disabled)) {
            $this->disabled = array();
        }

        $defaults = implode(',', $this->default_load_first);
        $lf = $this->plugin_config->value("Plugin_Manager", "load_first", $defaults);
        $this->load_first = explode(",", $lf);
        
        $this->load_list = array();
        if (is_array($this->load_first)) {
            foreach ($this->load_first as $val) {
                if ($this->testFile($val)) {
                    $this->load_list[$val] = $val;
                }
            }
        }
        
        $do_last = array();

        foreach ($this->plugin_list as $val) {
            if (! isset($this->load_list[$val]) && ! in_array($val, $this->default_load_last)) {
                $this->load_list[$val] = $val;
            } elseif (in_array($val, $this->default_load_last)) {
                $do_last[] = $val;
            }
        }
        
        foreach ($this->default_load_last as $val) {
            if (in_array($val, $do_last)) {
                $this->load_list[$val] = $val;
            }
        }
        
        foreach ($this->disabled as $val) {
            if (isset($this->load_list[$val])) {
                $this->load_list[$val] = false;
            }
        }
        
    }
    
    public function registerPlugin($obj) {
        if ($obj instanceof Plugin) {
            $name = get_class($obj);
            self::$registry[$name] = $name;
        }
    }

    /* Method: getConfig
     * Gets configuration data for plugins from XML files.
     * Merges the installation wide and per-blog settings if applicable.
     */

    function getConfig() {
        $global_config = NewConfigFile(USER_DATA_PATH.PATH_DELIM."plugins.xml");
        $blog_path = $this->getBlogPath();
        if ($blog_path) {
            $blog_config = NewConfigFile($blog_path.PATH_DELIM."plugins.xml");
            $blog_config->merge($global_config);
            $this->plugin_config = $blog_config;
        } else {
            $this->plugin_config = $global_config;
        }
    }

    /* Method: getPluginList
     * List all defined classes that are descended from Plugin.
     * This has the effect of getting a list of loaded plugins.
     *
     * Returns:
     * An array of class names.
     */

    function getPluginList() {
        return self::$registry;
    }

    /* Method: pluginLoaded
     * Determine if a specific plugin has been loaded.
     *
     * Parameters:
     * plugin_name - The name of the plugin class to check.
     * 
     * Returns:
     * True if the plugin class is loaded, false otherwise.
     */
    function pluginLoaded($plugin_name) {
        return class_exists($plugin_name);
    }

    /* Method: getFileList
     * List all the plugin files that get loaded.
     *
     * Returns:
     * An array of strings containing filenames.
     */

    function getFileList() {
        $plugin_dir_list = array();
        $blog_path = $this->getBlogPath();
        if ($blog_path) 
            $plugin_dir_list[] = $blog_path.PATH_DELIM."plugins";
        $plugin_dir_list[] = USER_DATA_PATH.PATH_DELIM."plugins";
        $plugin_dir_list[] = INSTALL_ROOT.PATH_DELIM."plugins";
        $file_list = array();
        foreach ($plugin_dir_list as $dir) {
            if (! is_dir($dir)) continue;
            $dirhand = opendir($dir);
            if ($dirhand) {
                $dir_list = array();
                while ( false !== ($ent = readdir($dirhand)) ) {
                    if (is_file($dir.PATH_DELIM.$ent) && 
                        strtolower(substr($ent, strrpos($ent, "."))) == ".php") {
                        $file_list[] = $ent;
                    }
    
                }
                closedir($dirhand);
            }
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
        $paths = array(USER_DATA_PATH, INSTALL_ROOT);
        $blog_path = $this->getBlogPath();
        if ($blog_path) {
            array_unshift($paths, $blog_path);
        }
        foreach ($this->load_list as $f=>$v) {
            if ($v) {
                foreach ($paths as $path) {
                    $file = Path::mk($path, 'plugins', $f);
                    if (file_exists($file)) {
                        $loader_function = include $file;
                        if (is_callable($loader_function)) {
                            $this->instance_list[$file] = $loader_function();
                        }
                    }
                }
            }
        }
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
        $paths = array(
            Path::mk(INSTALL_ROOT, 'plugins', $plug),
            Path::mk(USER_DATA_PATH, 'plugins', $plug),
        );
        $blog = NewBlog();
        if ($blog->isBlog()) {
            $paths[] = Path::mk($blog->home_path, 'plugins', $plug);
        }
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return true;
            }
        }
        return false;
    }

    public function getInstanceForFile(string $file) {
        return $this->instance_list[$file] ?? null;
    }
    
    private function getBlogPath() {
        if ( defined("BLOG_ROOT") ) {
            return BLOG_ROOT;
        } elseif (isset($_GET['blog'])) {
            $blogid = $_GET['blog'];
            $reg = SystemConfig::instance()->blogRegistry();
            if (isset($reg[$blogid])) {
                return $reg[$blogid]->path();
            }
        }
        return false;
    }
}
