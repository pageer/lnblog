<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005 Peter A. Geer <pageer@skepticats.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/
# File: plugin_loading.php
# Configuration page for determining which plugins are loaded and in what 
# order.  This can be done on a per-blog basis or installation-wide.

session_start();
require_once("blogconfig.php");
require_once("lib/creators.php");

global $PAGE;

function plug_sort($a, $b) {
	if (is_numeric($a["order"]) && ! is_numeric($b["order"])) {
		return -1;
	} elseif (! is_numeric($a["order"]) && is_numeric($b["order"])) {
		return 1;
	} elseif ($a["order"] > $b["order"]) {
		return 1;
	} elseif ($a["order"] < $b["order"]) {
		return -1;
	} elseif ($a["enabled"] && ! $b["enabled"]) {
		return -11;
	} elseif (! $a["enabled"] && $b["enabled"]) {
		return 1;
	} else {
		return 0;
	}
}

# Quick fix for name mangling on forms
function namefix($pg) {
	return preg_replace("/\W/", "_", $pg);
}

$user = NewUser();

global $PLUGIN_MANAGER;

if (defined("BLOG_ROOT")) {
	$blg = NewBlog();
	if (! $SYSTEM->canModify($blg, $user) || ! $user->checkLogin()) {
		$PAGE->redirect("login.php");
		exit;
	}
} elseif (! $user->isAdministrator() || ! $user->checkLogin()) {
	$PAGE->redirect("bloglogin.php");
	exit;
}

$tpl = NewTemplate(PLUGIN_LOAD_TEMPLATE);

if (has_post()) {

	$disabled = array();
	$first = array();
	
	foreach ($PLUGIN_MANAGER->plugin_list as $plug) {
		if (! POST(namefix($plug)."_en")) $disabled[] = $plug;
		if (is_numeric(POST(namefix($plug)."_ord"))) 
			$first[$plug] = POST(namefix($plug)."_ord");
	}
	asort($first);
	$lfirst = array();
	foreach ($first as $key=>$val) $lfirst[] = $key;
	
	$PLUGIN_MANAGER->exclude_list = $disabled; #implode(",", $disabled);
	$PLUGIN_MANAGER->load_first = $lfirst; #implode(",", $first);
	
	if (defined("BLOG_ROOT")) $file = BLOG_ROOT.PATH_DELIM."plugins.ini";
	else $file = USER_DATA_PATH.PATH_DELIM."plugins.ini";
	
	$parser = NewINIParser($file);
	$parser->setValue("Plugin_Manager", "exclude_list", implode(",",$disabled));
	$parser->setValue("Plugin_Manager", "load_first", implode(",",$lfirst));
	$ret = $parser->writeFile();
	
	if (! $ret) {
		$tpl->set("UPDATE_MESSAGE", spf_("Error updating file %s", $file));
	}
}

# Create an array of arrays to send to the template for display.

$disp_list = array();
foreach ($PLUGIN_MANAGER->plugin_list as $plug) {
	$disp_list[namefix($plug)] = 
		array("order"=>_("Unspecified"), 
		      "enabled"=> !in_array($plug, $PLUGIN_MANAGER->exclude_list),
		      "file"=>$plug);
}

$i=1;
foreach ($PLUGIN_MANAGER->load_first as $plug) {
	if (isset($disp_list[namefix($plug)])) 
		$disp_list[namefix($plug)]["order"] = $i++;
}

uasort($disp_list, "plug_sort");

$tpl->set("PLUGIN_LIST", $disp_list);

$PAGE->title = spf_("%s Plugin Loading Configuration", PACKAGE_NAME);
$PAGE->display($tpl->process());
?>
