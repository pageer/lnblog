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

$page = NewPage();
$user = NewUser();

global $PLUGIN_MANAGER;

if (defined("BLOG_ROOT")) {
	$blg = NewBlog();
	if (! $blg->canModifyBlog()) {
		$page->redirect("login.php");
	}
} elseif (! $user->checkLogin(ADMIN_USER)) {
	$page->redirect("bloglogin.php");
}

$tpl = NewTemplate(PLUGIN_LOAD_TEMPLATE);

if (has_post()) {

	$disabled = POST("exclude_list");
	$disabled = preg_replace("/(\s|,)+/", ",", $disabled);
	$first = POST("load_first");
	$first = preg_replace("/(\s|,)+/", ",", $first);
	$PLUGIN_MANAGER->exclude_list = explode(",", $disabled);
	$PLUGIN_MANAGER->load_first = explode(",", $first);
	
	if (defined("BLOG_ROOT")) $file = BLOG_ROOT.PATH_DELIM."plugins.ini";
	else $file = USER_DATA_PATH.PATH_DELIM."plugins.ini";
	
	$parser = NewINIParser($file);
	$parser->setValue("Plugin_Manager", "exclude_list", $disabled);
	$parser->setValue("Plugin_Manager", "load_first", $first);
	$ret = $parser->writeFile();
	
	if (! $ret) {
		$tpl->set("UPDATE_MESSAGE", spf_("Error updating file %s", $file));
	}
}

$tpl->set("PLUGIN_LIST", implode("\n", $PLUGIN_MANAGER->plugin_list));
$tpl->set("EXCLUDE_LIST", implode("\n", $PLUGIN_MANAGER->exclude_list));
$tpl->set("LOAD_FIRST", implode("\n", $PLUGIN_MANAGER->load_first));

$page->title = spf_("%s Plugin Loading Configuration", PACKAGE_NAME);
$page->display($tpl->process());
?>
