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

# File: plugin_setup.php
# A standard setup page for plugins.
#
# The actual configuration form must be supplied by the plugin itself.  
# Plugins that inherit from the abstract <Plugin> class can inherit a basic
# form, but others must create their own.

require_once("blogconfig.php");
require_once("lib/creators.php");
require_once("lib/plugins.php");

$page = NewPage();
$usr = NewUser();
if ($usr->uid != ADMIN_USER) {
	$page->redirect("bloglogin.php");
	exit;
}

if (has_post()) {

} elseif ( sanitize(GET("plugin")) && 
           class_exists(sanitize(GET("plugin"))) ) {
	$plug_name = sanitize(GET("plugin"));
	$plug = new $plug_name;
	$body = '<h4>'._('Plugin Configuration').'</h4>';
	$body .= '<ul><li>'._('Name').': '.get_class($plug).'</li>';
	$body .= '<li>'._('Version').': '.$plug->plugin_version.'</li>';
	$body .= '<li>'._('Description').': '.$plug->plugin_desc.'</li></ul>';
	ob_start();
	$ret = $plug->showConfig($page);
	$buff = ob_get_contents();
	ob_end_clean();
	$body .= is_string($ret) ? $ret : $buff;

} else {
	global $PLUGIN_MANAGER;
	$plug_list = $PLUGIN_MANAGER->getPluginList();
	$body = "<h4>"._('Plugin Configuration')."</h4><ul>";
	foreach ($plug_list as $plug) {
		$body .= '<li><a href="?plugin='.$plug.'">'.$plug.'"</a></li>';
	}
	$body .= '</ul>';
}
$page->title = spf_("%s Plugin Configuration", PACKAGE_NAME);
$page->display($body);
?>