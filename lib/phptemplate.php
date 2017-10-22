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
# Ihnerits:
# <LnBlogObject>

class PHPTemplate extends LnBlogObject {

    const ESC_HTML = 'htmlspecialchars';
    const ESC_URL = 'urlencode';

    protected $file;   # The name of the template file to use.
    protected $vars;   # An array of variables to register in the template.
    protected $search_paths = array();  # The paths on which to search for templates.

    # Property: helper_output
    # Whether HTML helpers should output their data in addition to returning it. */
    public $helper_output = true;

    public static $template_paths = array();

    public function __construct($file="") {
        $this->file = $file;
        $this->vars = array();
    }

    /* Method: get
       Get a template var as a string.

       Parameter:
       var - (string) The variable name
       escape - (boolean) Whether/how to escape the data.

       Returns
       The var value, or empty string if it is not set.
     */
    public function get($var, $escape = false) {
        $ret = '';
        if (isset($this->vars[$var])) {
            $ret = $this->vars[$var];
        }
        if ($ret && $escape) {
            $ret = $this->escape($ret, $escape);
        }
        return $ret;
    }

    # Method: raw
    # Same as get(), but echoes rather than return.
    public function raw($var, $escape = false) {
        echo $this->get($var, $escape);
    }

    # Method: put
    # Same as get(), but echos rather than return and escapes HTML by default.
    public function put($var, $escape = self::ESC_HTML) {
        echo $this->get($var, $escape);
    }

    /* Method: set
       Sets a template variable.

       Parameters:
       var - (string) The name of the variable.
       val - (mixed) Optional value for the variable.  Defaults to true.

       Returns:
       The value passed or true.
     */
    public function set($var, $val=true) {
        return $this->vars[$var] = $val;
    }

    /* Method: unsetVar
       Unsets a previously set template variable.

       var - (string) The name of the variable to unset.
     */
    public function unsetVar($var) {
        unset($this->vars[$var]);
    }

    /* Method: varSet
       Determine if a template variable has been set.

       Parameter:
       var - (string) The name of a template variable.

       Returns:
       True if var has been set, false otherwise.
     */
    public function varSet($var) {
        return isset($this->vars[$var]);
    }

    /* Method: reset
       Resets a template back to its empty state, clearing all variables and the file.

       Parameters:
       file - (string) An optional file name for the reset template.
     */
    public function reset($file="") {
        $this->file = $file;
        $this->vars = array();
    }

    # Mehtod: setSearchPath
    # Set the list of paths on which to search for template files.
    public function setSearchPath($paths) {
        $this->search_paths = $paths;
    }

    # Method: getSearchPath
    # Gets the search path for templates.  If a custom path list is set, it will return that.
    # Otherwise, it will return the following paths, if applicable:
    # - BLOG_ROOT/themes/THEME_NAME/templates
    # - BLOG_ROOT/templates
    # - USER_DATA_PATH/themes/THEME_NAME/templates
    # - INSTALL_ROOT/themes/THEME_NAME/templates
    # - INSTALL_ROOT/themes/default/templates
    #
    # Returns:
    # An array of path strings
    public function getSearchPath() {
        if ($this->search_paths) {
            return $this->search_paths;
        } else {
            $ret = array();
            if (defined('THEME_NAME') && THEME_NAME != 'default') {
                if (defined('BLOG_ROOT')) {
                    $ret[] = Path::mk(BLOG_ROOT, 'themes', THEME_NAME, 'templates');
                    $ret[] = Path::mk(BLOG_ROOT, 'templates');
                }
                if (defined('USER_DATA_PATH')) {
                    $ret[] = Path::mk(USER_DATA_PATH, 'themes', THEME_NAME, 'templates');
                }
                $ret[] = Path::mk(INSTALL_ROOT, 'themes', THEME_NAME, 'templates');
            } elseif (defined('BLOG_ROOT')) {
                $ret[] = Path::mk(BLOG_ROOT, 'templates');
            }
            $ret[] = Path::mk(INSTALL_ROOT, 'themes', 'default', 'templates');
            return $ret;
        }
    }

    public function getTemplatePath($file_name = null) {
        $file = $file_name ?: $this->file;
        foreach ($this->getSearchPath() as $path) {
            if (isset(self::$template_paths[$file])) {
                return self::$template_paths[$file];
            } elseif ($this->templateExists($path, $file)) {
                self::$template_paths[$file] = Path::mk($path, $file);
                return self::$template_paths[$file];
            }
        }
        return '';
    }

    protected function templateExists($path, $file_name = null) {
        $file = $file_name ?: $this->file;
        return file_exists(Path::mk($path, $file));
    }

    /* Method: process
       Process the template and get the output.

       Parameters:
       return_results - (boolean) Determines whether the output should be returned
                        in a string instead of sent straight to the client.  Default is true.
       Returns:
       The output string if return_results is true.  Otherwise, true on success and false on failure.
     */
    public function process($return_results=true) {
        ob_start();
        extract($this->vars, EXTR_OVERWRITE);
        include $this->getTemplatePath();
        if ($return_results) {
            $ret = ob_get_contents();
            ob_end_clean();
            return $ret;
        } else {
            return ob_end_flush();
        }
    }

    public function escape($data, $method, $options = array()) {
        switch ($method) {
            case self::ESC_HTML:
                return htmlspecialchars($data);
            case self::ESC_URL:
                return urlencode($data);
        }
        return '';
    }

    public function attributeValueEscape($val) {
        return $this->escape($val, self::ESC_HTML);
    }

    public function nodeContentEscape($val) {
        return $this->escape($val, self::ESC_HTML);
    }

    public function tag($name, $data=array()) {
        if (is_string($data)) {
            $data = array('content' => $data);
        }

        $ret = "<".$name;

        foreach ($data as $attr=>$val) {
            if ($attr != 'content') {
                $ret .= ' '.$attr.'="'.$this->attributeValueEscape($val).'"';
            }
        }

        if (isset($data['content'])) {
            $ret .= '>'.$this->nodeContentEscape($data['content']).'</'.$name.'>';
        } else {
            $ret .= ' />';
        }

        if ($this->helper_output) {
            echo $ret;
        }
        return $ret;
    }

    public function link($src, $content, $data=array()) {
        $data['content'] = $content;
        $data['href'] = $src;
        return $this->tag('a', $data);
    }

    public function maillink($address, $content, $data=array()) {
        return $this->link('mailto:'.$address, $content, $data);
    }

    public function img($src, $data=array()) {
        $data['src'] = $src;
        return $this->tag('img', $data);
    }
}
