<?php 
global $EVENT_REGISTER;
$EVENT_REGISTER->activateEventFull($tmp=false, "sidebar", "OnOutput");
$EVENT_REGISTER->activateEventFull($tmp=false, "sidebar", "OutputComplete");
?>
<a href="<?php echo PACKAGE_URL; ?>"><img alt="Powered by <?php echo PACKAGE_NAME; ?>" title="Powered by <?php echo PACKAGE_NAME; ?>" src="<?php echo getlink("logo.png", LINK_IMAGE); ?>" /></a>
