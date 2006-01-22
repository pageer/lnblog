<?php

class DisableComments extends Plugin {

	function DisableComments() {
		$this->plugin_desc = _("Allows you to globally disable comments or trackbacks for an entire blog.");
		$this->plugin_version = "0.1.0";
		$this->no_comment = "default";
		$this->no_trackback = false;
		$this->addOption("no_comment", _("Allow comments"),
		                 "default", "radio", 
		                 array("default" =>_("Use per-entry setting"),
		                       "loggedin"=>_("Logged in users only"),
		                       "disable" =>_("Disable all comments")));
		$this->addOption("no_trackback", _("Disable trackbacks for all entries"),
		                 false, "checkbox");
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
	}

}
$obj = new DisableComments();
$obj->registerEventHandler("blogentry", "InitComplete", "disable");
$obj->registerEventHandler("article", "InitComplete", "disable");

?>
