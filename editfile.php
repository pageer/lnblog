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

$file = GETPOST("file");
if (get_magic_quotes_gpc()) $file = stripslashes($file);
if (PATH_DELIM  != '/') $file = str_replace('/', PATH_DELIM, $file);

$u = NewUser();
$page = NewPage();
$blog = NewBlog();
$ent = NewBlogEntry();

$edit_ok = false;
$relpath = INSTALL_ROOT;

if ( GET("profile") == $u->username() ) {
	$edit_ok = true;
	$relpath = USER_DATA_PATH.PATH_DELIM.$u->username();
} elseif ($ent->isEntry() ) {
	$page->display_object = &$ent;
	$relpath = $ent->localpath();
	if ($ent->canModifyEntry() ) $edit_ok = true;
} elseif ($blog->isBlog() ) {
	$page->display_object = &$blog;
	$relpath = $blog->home_path;
	if ($blog->canModifyBlog() ) $edit_ok = true;
} elseif ($u->isAdministrator() ) {
	$edit_ok = true;
}
if (! $u->checkLogin()) $edit_ok = false;

if (! $edit_ok) {
	if (SERVER("referer")) $page->redirect(SERVER("referer"));
	else $page->redirect("index.php");
	exit;
}

$tpl = NewTemplate("file_edit_tpl.php");
$query_string = (isset($_GET["blog"]) ? "?blog=".$_GET["blog"] : "");
if (isset($_GET["profile"])) {
	$query_string .= ($query_string?"&amp;":"?")."profile=".$_GET["profile"];
}

# Prepare template for link list display.
if (isset($_GET["list"])) {
	$tpl->set("SHOW_LINK_EDITOR");
	$page->addScript("sitemap.js");
	$query_string .= ($query_string ? "&amp;" : "?")."list=yes";
}
$tpl->set("FORM_ACTION", current_file().$query_string);
if (isset($_GET["list"])) $tpl->set("PAGE_TITLE", "Edit Link List");
else $tpl->set("PAGE_TITLE", "Edit Text File");

if (! isset($_POST["file"])) $file = $relpath.PATH_DELIM.$file;

if (has_post()) {

	if (get_magic_quotes_gpc()) $data = stripslashes(POST("output"));
	else $data = POST("output");
	$ret = write_file($file, $data);

	if (! $ret) {
		$tpl->set("EDIT_ERROR", _("Cannot create file"));
		$tpl->set("ERROR_MESSAGE", 
		          spf_("Unable to create file %s.", $file));
		$tpl->set("FILE_TEXT", htmlentities($data));
	} #else $page->redirect("index.php");
	
} else {

	if (file_exists($file)) {
		$data = implode("", file($file));	
	} else {
		$data = "";
		$tpl->set("EDIT_ERROR", _("Create new file"));
		$tpl->set("ERROR_MESSAGE", _("The selected file does not exist.  It will be created."));
	}
}

$tpl->set("FILE_TEXT", htmlentities($data));
$tpl->set("FILE_PATH", $file);
$tpl->set("FILE_SIZE", file_exists($file)?filesize($file):0);
$tpl->set("FILE_URL", localpath_to_uri($file));	
$tpl->set("FILE", $file);

if (! defined("BLOG_ROOT")) $blog = false;

$page->title = _("Edit file");
$page->addStylesheet("form.css");
$page->display($tpl->process(), $blog);

?>
