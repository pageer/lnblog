<?php
# File: basic_layout_tpl.php
# This template is the default page layout.  It determines the position and, in
# part, the contents of the page banner, menubar, and sidebar.
#
# Note that the default banner, menubar, and sidebar are completely generated by
# standard plugins.  To, e.g., turn off one of the sidebar panels, all you have 
# to do is disable the corresponding plugin.  To change the order in which the
# panels are displayed, you can change the load order of the plugins.
#
# Some plugins that add things to the sidebar, menubar, or banner can
# be explicitly invoked.  To do this, you can either manually select the 
# "no event" option in an individual plugin's configuration, or you can set the
# EventForceOff option in the plugins section of your system.ini file to 1 (if 
# you do not do this, the plugin will show up twice) and then use the 
# load_plugin('plugin_name') function to invoke the plugin.
#
# The plugin_name string in the example above represents the name of the plugin
# class.  This is the same name that appears in the plugin configuration dialog.
?>
<body>
<!-- Site banner -->
<div id="banner">
    <div id="responsive-menu"><a href="#">&#9776;</a></div>
<?php
# Activate plugins that output the banner content.
#
# For custom code that displays before plugin-generated code, put it before this
# block.  For stuff that displays after plugin-generated code, put it after this
# block.  Obviouly, PHP code goes inside the PHP tags and HTML code goes 
# outside them.
#
# If you want to explicitly specify what plugins are loaded and in what order, 
# then you need to turn off the plugins' event detection and add a line to 
# invoke the plugin.  For example, the line below would add put the sidebar 
# calendar at the beginning of the banner (which is obviously not what you'd 
# want, but you get the general idea).
# load_plugin('sidebarcalendar');
#
# It probably goes without saying that the same comments here apply to the
# menubar and sidebar sections below.
EventRegister::instance()->activateEventFull($tmp=false, "banner", "OnOutput");
# Stuff to go between the "beginning" and "ending" plugins goes here.  Note
# that the default plugins all use the OnOutput event, so in practice, putting
# things here won't have any effect.
EventRegister::instance()->activateEventFull($tmp=false, "banner", "OutputComplete");
?>
    <!-- A menu/navigation bar -->
    <div id="menubar">
    <?php 
    # Activate the menubar plugins.
    EventRegister::instance()->activateEventFull($tmp=false, "menubar", "OnOutput");
    EventRegister::instance()->activateEventFull($tmp=false, "menubar", "OutputComplete");
    ?>
    </div>
</div>
<div id="main-wrapper">
    <!-- Main page content -->
    <div id="pagecontent">
    <?php echo $PAGE_CONTENT; ?>
    </div>
    <!-- A sidebar -->
    <div id="sidebar">
        <div id="responsive-close">
            <a href="#">
                <span class="close-label"><?php p_("Close menu")?></span>
                <span class="x">&times;</span>
            </a>
        </div>
    <?php 
    # Activate the sidebar plugins.
    EventRegister::instance()->activateEventFull($tmp=false, "sidebar", "OnOutput");
    EventRegister::instance()->activateEventFull($tmp=false, "sidebar", "OutputComplete");
    ?>
    </div>
</div>
</body>
