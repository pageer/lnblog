<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2017 Peter A. Geer <pageer@skepticats.com>

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
abstract class BasePages {

    protected $fs;

    abstract protected function getActionMap();
    abstract protected function defaultAction();

    public function __construct($fs = null) {
        $this->fs = $fs ? $fs : NewFS();
    }

    public function routeRequest($action = null) {
        $action_map = $this->getActionMap();
        $action = $action ?: GET('action');
        $action_method = isset($action_map[$action]) ? $action_map[$action] : null;

        if ($action_method && strpos($action_method, '::') !== false) {

            list($class, $method) = explode('::', $action_method, 2);
            $object = new $class();
            return $object->$method();

        } elseif ($action_method) {

            return $this->$action_method();

        } elseif ( isset($_GET['script']) ) {

            $file = $this->scriptPath($_GET['script']);
            $this->dumpAssetFile($file, "// Failed to find $file");
            return true;

        } elseif ( isset($_GET['style']) ) {

            $file = $this->stylePath($_GET['style']);
            $this->dumpAssetFile($file, "/* Failed to find $file */");
            return true;

        } elseif ( isset($_GET['plugin']) ) {

            define("PLUGIN_DO_OUTPUT", true);
            $plugin_name = preg_replace('[^A-Za-z0-9_\-]', '', $_GET['plugin']);
            $paths = array(@BLOG_ROOT, USER_DATA_PATH, INSTALL_ROOT);
            foreach ($paths as $path) {
                $plugin = Path::mk($path, 'plugins', "$plugin_name.php");
                if ($this->fs->file_exists($plugin)) {
                    require $plugin;
                    return true;
                }
            }
        }

        $this->defaultAction();
    }

    protected function getThemeAssetPath($type, $name) {
        if ( defined("BLOG_ROOT") &&
             file_exists(BLOG_ROOT."/$type/$name") ) {

            return BLOG_ROOT."/$type/$name";

        # Second case: Try the userdata directory
        } elseif ( defined('THEME_NAME') && defined('USER_DATA_PATH') &&
                   file_exists(USER_DATA_PATH.'/themes/'.THEME_NAME."/$type/$name") ) {
            return USER_DATA_PATH."/themes/".THEME_NAME."/$type/$name";

        # Third case: check the current theme directory
        } elseif ( defined('INSTALL_ROOT') && defined('THEME_NAME') &&
                   file_exists(INSTALL_ROOT."/themes/".THEME_NAME."/$type/$name") ) {
            return INSTALL_ROOT."/themes/".THEME_NAME."/$type/$name";

        # Fourth case: try the default theme
        } elseif ( defined('INSTALL_ROOT') &&
                   file_exists(INSTALL_ROOT."/themes/default/$type/$name") ) {
            return INSTALL_ROOT."/themes/default/$type/$name";

        # Last case: nothing found, so return the original string.
        } else {
            return $name;
        }
    }

    protected function getStylePath($name) {
        return $this->getThemeAssetPath("styles", $name);
    }

    protected function scriptPath($name) {
        return $this->getThemeAssetPath("scripts", $name);
    }

    private function dumpAssetFile($file, $default) {
        if ($this->fs->file_exists($file)) {
           $this->fs->readfile($file);
        } else {
            $this->fs->echo_to_output($default);
        }
    }
}