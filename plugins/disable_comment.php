<?php

# Plugin: DisableComments
# Close comments or trackbacks on selected entries.
#
# This plugin allows you to disable comments or trackbacks on all the entries
# in a blog.  You have several options for this.  For comments, you can
# disable all comments, allow comments only by logged-in users, or use the
# per-entry setting (which is the default).  For trackbacks, you can just
# turn them all off.
#
# There is also a time-delay option.  You can just enter a number of days 
# and both comments and trackbacks will be disabled on all entries more than
# that number of days old.  
#
# Note that this plugin does dynamic disabling, so the actual entry files 
# will not be modified.  Thus, if you edit an old entry, or you will still
# the the checkbox to allow comments as enabled.  This plugin automatically
# overrides all such settings.

class DisableComments extends Plugin {

	function DisableComments() {
		$this->plugin_desc = _("Allows you to globally disable comments, trackbacks, or pingbacks for an entire blog.");
		$this->plugin_version = "0.2.0";
		$this->no_comment = "default";
		$this->no_trackback = false;
		$this->close_old = "";
		$this->addOption("no_comment", _("Allow comments"),
		                 "default", "radio", 
		                 array("default" =>_("Use per-entry setting"),
		                       "loggedin"=>_("Logged in users only"),
		                       "disable" =>_("Disable all comments")));
		$this->addOption("no_trackback", _("Disable trackbacks for all entries"),
		                 false, "checkbox");
		$this->addOption("no_pingback", _("Disable pingbacks for all entries"),
		                 false, "checkbox");
		$this->addOption("close_old", 
				_("Close all replies on entries older than this many days"),
				"", "text");
		$this->getConfig();
	}

	function disable(&$param) {
		
		if ($this->no_comment == "disable") {
			$param->allow_comment = false;
		} elseif ($this->no_comment == "loggedin") {
			$usr = NewUser();
			if (! $usr->checkLogin()) $param->allow_comment = false;
		}
		if ($this->no_trackback) $param->allow_tb = false;
		if ($this->no_pingback) $param->allow_pingback = false;

		if (is_numeric($this->close_old) && $this->close_old > 0) {
			# Subtract the number of days * 86400 seconds/day from the 
			# current time to get the target timestamp.
			$close_time = time() - $this->close_old * 86400;
			if ($param->post_ts < $close_time) {
				$param->allow_comment = false;
				$param->allow_tb = false;
				$param->allow_pingback = false;
			}
		}
	}

}
$obj =& new DisableComments();
$obj->registerEventHandler("blogentry", "InitComplete", "disable");
$obj->registerEventHandler("article", "InitComplete", "disable");

?>
