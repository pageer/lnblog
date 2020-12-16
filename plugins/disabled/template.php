<?php
# File: Plugin Template For Developers
# This is a very basic, commented plugin template for instructional purposes.
# You may use it as the basis for your own plugins if you wish.
#
# Section: Overview
# This is a simple template for creating an LnBlog plugin.
# This file illustrates the recommended way to create an LnBlog plugin,
# but it is not the only way.  Be careful if you deviate from this design,
# because things will get more complicated.
#
# If you want to contribute a plugin to be included with LnBlog, or if you
# just want more information regarding plugins, please feel free to send me
# an e-mail at <pageer@skepticats.com> or leave a comment at the LnBlog
# home page.
#
# Section: Basic Design
# LnBlog provides an abstract Plugin class.  This class defines not only the
# interface of plugin classes, but some default configuration functionality.
# It also provides a slightly simplified interface to the event system.  It
# is therefore recommended that you create your plugin as a class that
# inherits from the Plugin class.  This is not absolutely required, but if
# you choose not to inherit from the Plugin class, your plugin will not be
# recognized by the plugin manager and will therefore not be able to take
# advantage of the built-in plugin configuration system.
#
# One further thing to note is that you absolutely MUST NOT have any
# characters, including whitespace, outside of the PHP tags.  The simple act
# of including the plugin file should never create output.

# Class: MyPlugin
# As mentioned above, you class should extend the <Plugin> base class.
class MyPlugin extends Plugin
{
    public $myname;

    # Method: __construct
    # Make sure that you provide a constructor for your class.
    # The constructor should not take any arguments and should set the
    # plugin_desc and plugin_version properties.  Anything else in the
    # constructor is purely optional.
    # Note, however, that the constructor absolutely MUST NOT perform any
    # output, as this will muck up the entire plugin system.

    public function __construct() {
        $this->plugin_desc = "My very own LnBlog plugin.";
        $this->plugin_version = "0.1.0";

        # Section: addOption()
        # Here we will set up the plugin configuration system.
        # The configuration methods are inherited from the Plugin base class
        # and provide you with a method to get user input and persist it.
        # To use the configuration system, you just need to call the
        # addOption method with the appropriate values.
        #
        # The first argument for addOption is the variable name.  This will
        # be the name stored in the plugins.ini file.  It will also be used
        # as the name of a member variable, so you will be able to access
        # this setting through the variable $this->myname.
        # The second argument is a description of the setting.  This will be
        # displayed to the user on the plugin configuration page.  Note that
        # the text is internationalized using the _() function.
        # The third argument is a default value.  This is simply the value
        # assigned to the variable if the user has not set it.
        # The forth is the optional control type.  This maps directly to HTML
        # input controls and can be "text" (the default), "checkbox", "option",
        # or "select".
        # Last is an optional parameter for the possible values for option and
        # select controls.  It is an array of the form value=>description.
        # This is not given in the example, as it only applies to option
        # and select controls.
        $this->addOption(
            "myname",
            _("The name to display"),
            _("Bob Smith"),
            "text"
        );

        # Section: getConfig()
        # Lastly, we load the stored configuration for this plugin.
        # Note that there is a global configuration for the LnBlog installation
        # as well as a per-blog configuration.  The getConfig method will merge
        # these two, with the per-blog settings overriding the
        # installation-wide settings.
        $this->getConfig();
    }

    # Method: myOutput
    # Your plugin will probably need at least one callback function.
    # For those unfamiliar with event-driven programming, a callback function
    # is simply a function that is called by the event system when a certain
    # thing happens.  For example, in a word processor, when you click on the
    # File|Save menu item, a callback function is activated which saves your
    # document.  This same function is called when you click the save button
    # on the toolbar.
    #
    # LnBlog's event system is similar, although the events are not the same
    # you would use on desktop software.  You can call the same function
    # for several events and you can have one event activate several different
    # callback functions.
    #
    # Note that the callback function takes a single reference parameter.
    # This is because the event system passes an instance of the class that
    # raised the event to the callback function.  So, if the callback is
    # activated from an event raised by a BlogEntry, then $param will be
    # a BlogEntry object.  If the same function will be called by events
    # raisedby multiple classes, then you should do type checking on $param
    # so as to avoid those annoying error messages. ;)
    #
    # This function simply dumps some output to the screen, but you could
    # obviously do more complicated things if you want.  Note that it is
    # possible to escape to HTML mode inside the function body.
    #
    # Parameters:
    # $param - The $param argument is the object that is passed to the handler
    #          by the event system.  This is, effectively, the object upon which
    #          the event was invoked.  In this case, the handler is registered with
    #          the blogentry OnOutput event, which means that $param will be a BlogEntry object.
    public function myOutput($param) {
?>
<p>My name is <?php echo $this->myname; ?>.  This is my plugin!</p>
<?php
    }

    # Method: myStaticOutput
    # This function will be used below to illustrate static methods.
    # Note that this function does not use any member variables.
    #
    # Parameters:
    # $param - This is the same as the non-static version.
    public static function myStaticOutput($param) {
?>
<p>This is some static output.</p>
<?php
    }

}

# Section: Instantiation
# Here, outside the class declaration, is where we attach the callback
# function to an event.
# First, we create an instance of the class.
#
# After that, we can use the registerEventHandler() and
# registerStaticEventHandler() methods to attach the event handler and call
# it as either a member function or a static function, respectively.
# The difference is that static functions do not require an instance of the
# class to be created, which means they cannot use any member variables
# of the class.  They are good mainly for things like doing output.
#
# The arguments to these methods are, in order, a class name, event name,
# and the name of your callback function.  Note that the class name can
# either be the name of a real class, e.g. "blogentry", or a place-holder,
# such as "sidebar".  If it is a real class, the function will be passed
# an instance of it.
#
# As a further note, be careful what you put outside the class declaration,
# as all code outside class and function declarations will be run when each
# page is initialized, i.e. before anything else is done.

$plug = new MyPlugin();
$plug->registerEventHandler("blogentry", "OnOutput", "myOutput");
$plug->registerStaticEventHandler("page", "OnOutput", "myStaticOutput");

