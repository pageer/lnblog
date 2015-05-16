<?php
# File: archive_layout_tpl.php
# This template is the optional page layout for archived entries.  If the 
# archive layout is enabled for a blog, then this template will be used instead
# of <basic_layout_tpl.php> when the entry permalink is displayed.
#
# The main difference between this and the basic layout is that the sidebar is 
# omitted and the body class is different.  Aside from that, all the same rules 
# and ideas discussed in the <basic_layout_tpl.php> documentation apply.
#
?>
<body>
<!-- Site banner -->
<div id="banner">
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
</div>
<!-- A menu/navigation bar -->
<div id="menubar">
<?php 
# Activate the menubar plugins.
EventRegister::instance()->activateEventFull($tmp=false, "menubar", "OnOutput");
EventRegister::instance()->activateEventFull($tmp=false, "menubar", "OutputComplete");
?>
</div>
<!-- Main page content -->
<div id="pagecontent">
<?php echo $PAGE_CONTENT; ?>
</div>

</div>
</body>