<?php

# template.php - A template for LnBlog plugins.
# Copyright (C) 2005, Peter A. Geer <pageer@skepticats.com>
# You are free to use, modify, and redistribute the program as you like.

# This is a simple template for creating an LnBlog plugin.
# This file illustrates the recommended way to create an LnBlog plugin,
# but it is not the only way.  Be careful if you deviate from this design,
# because things will get more complicated.
#
# If you want to contribute a plugin to be included with LnBlog, or if you
# just want more information regarding plugins, please feel free to send me
# an e-mail at pageer@skepticats.com or leave a comment at the LnBlog
# home page.

# Basic Design

# LnBlog provides an abstract Plugin class.  This class defines not only the
# interface of plugin classes, but some default configuration functionality.
# It also provides a slightly simplified interface to the event system.  It 
# is therefore recommended that you create your plugin as a class that 
# inherits from the Plugin class.  This is not absolutely required, but if 
# you choose not to inherit from the Plugin class, your plugin will not be
# recognized by the plugin manager and will therefore not be able to take
# advantage of the built-in plugin configuration system.

# One further thing to note is that you absolutely MUST NOT have any 
# characters, including whitespace, outside of the PHP tags.  The simple act
# of including the plugin file should never create output.

class MyPlugin extends Plugin {

	# Make sure that you provide a constructor for your class.
	# The constructor should not take any arguments and should set the
	# plugin_desc and plugin_version properties.  Anything else in the 
	# constructor is purely optional.
	# Note, however, that the constructor absolutely MUST NOT perform any
	# output, as this will muck up the entire plugin system.

	function MyPlugin() {
		$this->plugin_desc = "My very own LnBlog plugin.";
		$this->plugin_version = "0.1.0";

		# Here we will set up the plugin configuration system.
		# The configuration methods are inherited from the Plugin base class
		# and provide you with a method to get user input and persist it.
		# To use the configuration system, you need to define some member 
		# variables and an array.

		# We will use one member variable, for a user's name.  You should 
		# *always* provide a default value for member variables.
		# Note that we enclose the string literal in _() in order to allow for
		# it to be translated and localized.
		$this->myname = _("Bob Smith");

		# The member_list array is an array of arrays which *must* contain
		# an entry for every member variable that can be set by the user.  The
		# variable name is the key and the value is a variable length array of
		# settings for the variable.  The only required setting is the 
		# "description", which will be displayed on the configuration screen.
		# You should also set "default", which is the default value for the
		# variable, and the control, which determines what kind of control
		# will be used to get the value.  You can choose from "text", 
		# "checkbox", "radio", and "select".
		$this->member_list = array();
		$this->member_list["myname"] = 
			array("description"=>_("The name to display"),
			      "default"=>_("Bob Smith"),
			      "control"=>"text");

		# Lastly, we load the stored configuration for this plugin.
		# Note that there is a global configuration for the LnBlog installation
		# as well as a per-blog configuration.  The getConfig method will merge
		# these two, with the per-blog settings overriding the 
		# installation-wide settings.
		$this->getConfig();		
	}
	
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
	
	function myOutput(&$param) {
?>
<p>My name is <?php echo $this->myname; ?>.  This is my plugin!</p>
<?php
	}

	# This function will be used below to illustrate static methods.
	# Note that this function does not use any member variables.

	function myStaticOutput(&$param) {
?>
<p>This is some static output.</p>
<?php
	}

}

# Here, outside the class declaration, is where we attach the callback
# function to an event.  
# First, we create an instance of the class. 

$plug = new MyPlugin();

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

$plug->registerEventHandler("blogentry", "OnOutput", "myOutput");
$plug->registerStaticEventHandler("page", "OnOutput", "myStaticOutput");
?>
