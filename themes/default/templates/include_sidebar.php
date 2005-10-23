<?php 
global $EVENT_REGISTER;
$EVENT_REGISTER->activateEventFull($tmp=false, "sidebar", "OnOutput");
$EVENT_REGISTER->activateEventFull($tmp=false, "sidebar", "OutputComplete");
?>