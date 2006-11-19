<body>
<!-- Container div for fixed-width layout. -->
<div id="container">
<!-- Rounded top border -->
<div class="roundtop"><img src="<?php echo getlink("tl.png");?>" alt="" width="15" height="15" class="corner" style="display: none" /></div>
<!-- Site banner -->
<div id="banner">
<?php
global $EVENT_REGISTER;
$EVENT_REGISTER->activateEventFull($tmp=false, "banner", "OnOutput");
$EVENT_REGISTER->activateEventFull($tmp=false, "banner", "OutputComplete");
?>
</div>
<!-- A menu/navigation bar -->
<div id="menubar">
<?php
global $EVENT_REGISTER;
$EVENT_REGISTER->activateEventFull($tmp=false, "menubar", "OnOutput");
$EVENT_REGISTER->activateEventFull($tmp=false, "menubar", "OutputComplete");
?>
</div>
<div id="maincontainer">
<!-- A sidebar -->
<div id="sidebar">
<?php
global $EVENT_REGISTER;
$EVENT_REGISTER->activateEventFull($tmp=false, "sidebar", "OnOutput");
$EVENT_REGISTER->activateEventFull($tmp=false, "sidebar", "OutputComplete");
?>
</div>
<!-- Main page content -->
<div id="pagecontent">
<?php echo $PAGE_CONTENT; ?>
</div>
</div>
<!-- Rounded bottom border -->
<div class="roundbottom"><img src="<?php echo getlink("bl.png");?>" alt="" width="15" height="15" class="corner" style="display: none" /></div>
</div>
</body>
