<body>
<!-- A menu/navigation bar -->
<div id="menubar">
<?php
EventRegister::instance()->activateEventFull($tmp=false, "menubar", "OnOutput");
EventRegister::instance()->activateEventFull($tmp=false, "menubar", "OutputComplete");
?>
</div>
<!-- Site banner -->
<div id="banner">
<?php
EventRegister::instance()->activateEventFull($tmp=false, "banner", "OnOutput");
# Stuff to go between the "beginning" and "ending" plugins goes here.  Note
# that the default plugins all use the OnOutput event, so in practice, putting
# things here won't have any effect.
EventRegister::instance()->activateEventFull($tmp=false, "banner", "OutputComplete");
?>
<div style="clear:both"></div>
</div>
<!-- Main page content -->
<div id="pagecontent">
<?php echo $PAGE_CONTENT; ?>
</div>
<!-- A sidebar -->
<div id="sidebar">
<?php
EventRegister::instance()->activateEventFull($tmp=false, "sidebar", "OnOutput");
EventRegister::instance()->activateEventFull($tmp=false, "sidebar", "OutputComplete");
?>
</div>
</body>
