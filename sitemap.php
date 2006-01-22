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

# File: sitemap.php
# Used to edit the site map in the menu bar.
#
# This page provides a form and some simple JavaScript to create a list
# of HTML links separated by newlines.  This is read by the standard menubar
# plugin and converted into an unordered list of links which can easily 
# be styled with CSS.
#
# In the standard setup, this file is included by the per-blog map.php file.

session_start();

require_once("blogconfig.php");
require_once("lib/creators.php");
require_once("lib/utils.php");

global $PLUGIN_MANAGER;
$u = NewUser();
$page = NewPage();
$blog = NewBlog();

if ($blog->isBlog()) {
	$page->display_object = &$blog;
	if (! $blog->canModifyBlog() ) {
		$page->redirect("index.php");
		exit;
	}
} elseif (! $u->checkLogin() && ! $u->isAdministrator() ) {
	$page->redirect("index.php");
	exit;
}

$tpl = NewTemplate("file_edit_tpl.php");
$tpl->set("PAGE_TITLE", _("Create site map"));
$tpl->set("FORM_MESSAGE", spf_('This page will help you create a site map to display in the navigation bar at the top of your blog.  This file is stored under the name %s in the root directory of your weblog for a personal sitemap or in the %s installation directory for the system default.  This file in simply a series of <abbr title="Hypertext Markup Language">HTML</abbr> links, each on it\'s own line, which the template will process into a list.  If you require a more complicated menu bar, you will have to create a custom template.',
SITEMAP_FILE, PACKAGE_NAME));
$tpl->set("FORM_ACTION", current_file() );
$tpl->set("SHOW_LINK_EDITOR");

if (has_post()) {

	if ($blog->isBlog()) {
		$target_file = BLOG_ROOT.PATH_DELIM.
			$PLUGIN_MANAGER->plugin_config;
	} else {
		$target_file = USER_DATA_PATH.PATH_DELIM.SITEMAP_FILE;
	}

	if (get_magic_quotes_gpc()) $data = stripslashes(POST("output"));
	else $data = POST("output");
	$ret = write_file($target_file, $data);

	if (! $ret) {
		$tpl->set("EDIT_ERROR", _("Cannot create file"));
		$tpl->set("ERROR_MESSAGE", 
		          spf_("Unable to create file %s.", $target_file));
		$tpl->set("FILE_TEXT", $data);
	} else $page->redirect("index.php");
	
} else {

	if ($blog->isBlog() && is_file(BLOG_ROOT.PATH_DELIM.SITEMAP_FILE) ) {
		$target_file = BLOG_ROOT.PATH_DELIM.SITEMAP_FILE;
	} elseif (is_file(USER_DATA_PATH.PATH_DELIM.SITEMAP_FILE) ) {
		$target_file = USER_DATA_PATH.PATH_DELIM.SITEMAP_FILE;
	} else {
		$target_file = false;
	}

	if ($target_file) {
		$data = implode("", file($target_file) );
		$tpl->set("FILE_TEXT", $data);
	}
	
}

if (! defined("BLOG_ROOT")) $blog = false;

if ($blog->isBlog()) $page->title = _("Edit blog menu bar");
else $page->title = _("Edit site menu bar");
$page->addStylesheet("form.css");
$page->addScript("sitemap.js");
$page->display($tpl->process(), $blog);

?>
