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

class PluginSettings
{
 
}

abstract class Plugin extends LnBlogObject
{

    /*  Property: plugin_desc
    A short description of the plugin. */
    public $plugin_desc = 'Abstract plugin';
    /* Property: plugin_version
    The version number of the plugin.  This should be in "1.2.3" format. */
    public $plugin_version = '0.0.0';
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
                  values are "text", "checkbox", "radio", "select", "file".
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
    public $member_list = array();

    public $no_event;

    /* Constructor:
    Insert initialization code into the constructor.  You MUST OVERRIDE
    the constructor for your concrete subclass (i.e. you must have an
    explicit constructor). */

    public function __construct() {
        PluginManager::instance()->registerPlugin($this);
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

    public function addOption($name, $description, $default, $control="text", $options=false) {
        if (! isset($this->member_list)) $this->member_list = array();
        $this->$name = $default;
        $this->member_list[$name] =
            array("description"=>$description,
                  "default"=>$default, "control"=>$control);
        if ($options) $this->member_list[$name]["options"] = $options;
    }

    # Method: addNoEventOption
    # Add an option to turn off event output handlers.
    public function addNoEventOption() {
        $this->addOption(
            'no_event',
            _('No event handlers - do output when plugin is created'),
            System::instance()->sys_ini->value("plugins", "EventDefaultOff", 0),
            'checkbox'
        );
    }

    # Method: registerNoEventOutputHandler
    # Register a handler to do output in the case that "no event" is turned
    # off.
    public function registerNoEventOutputHandler($target, $function_name) {
        $system_default = System::instance()->sys_ini->value("plugins", "EventForceOff", 0);
        if (!$this->no_event && !$system_default) {
            $this->registerEventHandler($target, "OnOutput", $function_name);
        }
    }

    # Method: registerNoEventOutputCompleteHandler
    # Register a handler to do output in the case that "no event" is turned
    # off.
    public function registerNoEventOutputCompleteHandler($target, $function_name) {
        $system_default = System::instance()->sys_ini->value("plugins", "EventForceOff", 0);
        if (!$this->no_event && !$system_default) {
            $this->registerEventHandler($target, "OutputComplete", $function_name);
        }
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

    public function showConfig($page, $csrf_token) {
        if (! $this->member_list) return false;

        echo "<fieldset>\n";
        echo '<form method="post" ';
        echo 'action="'.current_uri(true).'" ';
        echo "id=\"plugin_config\">\n";
        echo '<input type="hidden" name="' . 
            BasePages::TOKEN_POST_FIELD .
            '" value="' . $csrf_token . '" />';

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
                    echo '<input name="'.$mem.'" id="'.$mem.'" type="radio" value="'.$val.'"';
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
            } elseif ($config["control"] == "file") {
                echo '<div>';
                echo '<label for="'.$mem.'">'.$config['description']."</label>\n";
                echo '<input name="'.$mem.'" id="'.$mem.'" type="text" value="'.$this->$mem.'" />';
                echo '<input name="'.$mem.'_upload" id="'.$mem.'_upload" type="file" />';
                echo "</div>\n";
            } elseif ($config["control"] == "textarea") {
                echo '<div>';
                echo '<label for="'.$mem.'">'.$config["description"].'</label>';
                echo '<textarea name="'.$mem.'" id="'.$mem.'" rows="10" cols="50">'.$this->$mem.'</textarea>';
                echo "</div>\n";

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
    # Note that this also handles uploaded files.  If run from a blog, the file is
    # uploaded to the blog root.  Otherwise, it goes to the userdata directory.
    #
    # Returns:
    # True on success, false on failure.

    public function updateConfig() {
        if (! $this->member_list) return false;
        if (defined("BLOG_ROOT")) {
            $parser = NewConfigFile(BLOG_ROOT.PATH_DELIM."plugins.xml");
            $ul_path = BLOG_ROOT;
        } else {
            $parser = NewConfigFile(USER_DATA_PATH.PATH_DELIM."plugins.xml");
            $ul_path = USER_DATA_PATH;
        }
        foreach ($this->member_list as $mem=>$config) {
            if (isset($config["control"]) && $config["control"] == "checkbox") {
                $this->$mem = (POST($mem) ? "1":"0");
            } elseif (isset($config["control"]) && $config["control"] == "file") {
                $upld = NewFileUpload($mem."_upload", $ul_path);
                if ( $upld->completed() ) {
                    $upld->moveFile();
                    $this->$mem = $upld->destname;
                }
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
        $this->invalidateCache();
        return $ret;
    }

    # Method: getConfig
    # Reads the configuration data for the plugin from a file and stores it
    # in class variables.

    public function getConfig() {
        $parser = PluginManager::instance()->plugin_config;
        foreach ($this->member_list as $mem=>$config) {
            $this->$mem = (isset($config["default"]) ? $config["default"] : "");
            $val = $parser->value(get_class($this), $mem, $this->$mem);
            if ($val == "1" || $val == "0") $this->$mem = intval($val);
            else $this->$mem = $val;
        }
    }

    # Section: Cache management functions

    # Method: cachepath
    # Gets the path to the cache file for this plugin.  Note that there is, by default, only
    # one such file per plugin.  Plugins that need more must override this method.
    #
    # Parameters:
    # obj - The object to which the cache applies.  In the default implementation, this is
    #       the current blog.  Plugins which want to store cache data in an entry directory,
    #       or a path based on some other object, must *override* this method.
    #
    # Returns:
    # A string representing the local filesystem path to which cach data will be written.
    # In the default implementation, this has the form BLOGROOT/cache/PLUGINCLASS_output.cache.

    public function cachepath($obj) {
        if (method_exists($obj, "isBlog") && $obj->isBlog())
            /** @var Blog $obj */
            return Path::mk($obj->home_path, "cache", get_class($this)."_output.cache");
            else return false;
    }

    # Method: invalidateCache
    # Invalidates, i.e. deletes, the cache file for this plugin.
    #
    # Parameters:
    # obj - Same as for <cachepath>.  If not specified, the current blog is used.  Note that
    #       this parameter is passed on to <cachepath> and so can be used for implementing
    #       multi-file caches.

    public function invalidateCache($obj=false) {
        if ( ! is_a($obj, 'Blog')) $b = NewBlog();
        else $b = $obj;
        $f = NewFS();

        $cache_path = $this->cachepath($b);
        if (file_exists($cache_path)) {
            return $f->delete($cache_path);
        } else return true;
    }

    # Method: buildOutput
    # Method called by <outputCache> to regenerate cache data.  Plugins using the
    # standard cache system *must* override this method to do their output.
    #
    # Parameters:
    # obj - An object to which this cache applies, as with <invalidateCache> and others.
    #
    # Returns:
    # A string of data to send to the client.

    public function buildOutput($obj) {
        return '';
    }

    # Method: outputCache
    # Dumps the contents of the cache file to the browser.  If the cache file exists, then
    # the data comes from there.  Otherwise, <buildOutput> is called and the result used to
    # create a fresh cache file.  Note that if the class has an enable_cache member and it is
    # set to false, then the cache will be bypassed and only the result of <buildOutput> will
    # be sent to the browser.
    #
    # Parameters:
    # obj - Object to which the cache applies.
    # suppress_login - Don't display the cached data when the user is logged
    #                  in.  This allows for users with different permission
    #                  levels to still see different pages with caching on.

    public function outputCache($obj=false, $suppress_login=true) {

        $b = is_a($obj, 'Blog') ? $obj : NewBlog();
        $u = NewUser();
        $f = NewFS();

        $content = $this->buildOutput($b);

        if (! $content && empty($this->allow_empty_output)) {
            return;
        }

        if ( (isset($this->enable_cache) && ! $this->enable_cache) ||
             $u->checkLogin() ) {
            echo $content;
        } else {
            $cache_path = $this->cachepath($b);

            if ($cache_path && ! file_exists($cache_path)) {
                if (! is_dir(dirname($cache_path))) {
                    $f->mkdir_rec(dirname($cache_path));
                }
                if (is_dir(dirname($cache_path)))
                    $f->write_file($cache_path, $content);
            }

            if (file_exists($cache_path)) {
                readfile($cache_path);
            }
        }
    }

    # Method: registerStandardInvalidators
    # Registers the standard set of cache invalidator events.  This registers the <invalidateCache>
    # method as an event handler for the UpdateComplete, InsertComplete, and DeleteComplete
    # events for the BlogEntry and Article classes, as well as the UpdateComplete event of the
    # blog class.

    public function registerStandardInvalidators() {
        $this->registerEventHandler("blogentry", "UpdateComplete", "invalidateCache");
        $this->registerEventHandler("blogentry", "InsertComplete", "invalidateCache");
        $this->registerEventHandler("blogentry", "DeleteComplete", "invalidateCache");
        $this->registerEventHandler("article", "UpdateComplete", "invalidateCache");
        $this->registerEventHandler("article", "InsertComplete", "invalidateCache");
        $this->registerEventHandler("article", "DeleteComplete", "invalidateCache");
        $this->registerEventHandler("blog", "UpdateComplete", "invalidateCache");
    }
}
