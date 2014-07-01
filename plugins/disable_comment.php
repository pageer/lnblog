<?php
# Plugin: DisableComments
# This plugin allows you to disable comments, trackbacks, or pingbacks on all the entries
# in a blog.  For comments, you have a few options for how to do this.  You can
# - Disable all comments, period.
# - Allow comments only by logged-in users.
# - Use the per-entry "allow comments" setting, which is the default.
# For trackbacks and pingbacks, things are much simpler: you can either disable
# them completely or not.  
#
# There is also a time-delay option.  You can just enter a number of days 
# and all replies (comments, trackbacks, and pingbacks) will be disabled on all entries more than
# that number of days old.  
#
# Note that this plugin does dynamic disabling, so the actual entry data
# will not be modified.  Thus, if you edit an old entry that allowed comments, you may still
# see the checkbox to allow comments as enabled.  This plugin automatically
# overrides that setting when enabled.  Note that this also means that if you later turn
# off this plugin, comments will be re-enabled on those entries.

class DisableComments extends Plugin {

	function __construct() {
		$this->plugin_desc = _("Allows you to globally disable comments, trackbacks, or pingbacks for an entire blog.");
		$this->plugin_version = "0.2.1";
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
		parent::__construct();
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
$obj = new DisableComments();
$obj->registerEventHandler("blogentry", "InitComplete", "disable");
$obj->registerEventHandler("article", "InitComplete", "disable");
