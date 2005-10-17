<?php
global $EVENT_REGISTER;
$EVENT_REGISTER->activateEventFull($tmp=false, "banner", "OnOutput");
$EVENT_REGISTER->activateEventFull($tmp=false, "banner", "OutputComplete");
# If there are no plugins for the banner, then just fall back to the default.
if (! $EVENT_REGISTER->hasHandlers("banner", "OnOutput") &&
    ! $EVENT_REGISTER->hasHandlers("banner", "OnOutputComplete") ) {
	if (defined("BLOG_ROOT")) { 
?>
<h1><a href="<?php echo $BLOG_URL; ?>" title="<?php echo $BLOG_DESCRIPTION; ?>"><?php echo $BLOG_NAME; ?></a></h1>
<?php 
	} else { 
?>
<h1><a href="<?php echo PACKAGE_URL; ?>"><?php echo PACKAGE_NAME; ?></a></h1>
<?php 
	} 
}?>
