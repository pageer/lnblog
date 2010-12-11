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

# File: editfile.php
# Used to edit an arbitrary text file.
#
# This page provides a form and some simple JavaScript to create a list
# of HTML links separated by newlines.  This is read by the standard menubar
# plugin and converted into an unordered list of links which can easily 
# be styled with CSS.
#
# In the standard setup, this file is included by the per-blog map.php file.

session_start();

require_once("config.php");
require_once("lib/creators.php");
require_once("lib/utils.php");

global $PAGE;

$file = GET("file");
if (PATH_DELIM  != '/') $file = str_replace('/', PATH_DELIM, $file);
$file = str_replace("..".PATH_DELIM, '', $file);

$u = NewUser();
$blog = NewBlog();
$ent = NewBlogEntry();
$p = new Path();

$edit_ok = false;
$relpath = INSTALL_ROOT;

if ( GET("profile") == $u->username() ) {
	$edit_ok = true;
	$relpath = Path::get(USER_DATA_PATH, $u->username());
} elseif ($ent->isEntry() ) {
	$PAGE->setDisplayObject($ent);
	$relpath = $ent->localpath();
	if ($SYSTEM->canModify($ent, $u) ) $edit_ok = true;
} elseif ($blog->isBlog() ) {
	$PAGE->setDisplayObject($blog);
	$relpath = $blog->home_path;
	if ($SYSTEM->canModify($blog, $u) ) $edit_ok = true;
} elseif ($u->isAdministrator() ) {
	$edit_ok = true;
}

if (! $u->checkLogin()) $edit_ok = false;

if (! $edit_ok) {
	if (SERVER("referer")) $PAGE->redirect(SERVER("referer"));
	else {
		header("HTTP/1.0 403 Forbidden");
		p_("You do not have permission to edit this file.");
	}
	exit;
}

$tpl = NewTemplate("file_edit_tpl.php");

# Prepare template for link list display.
if (GET("list")) {
	$tpl->set("SHOW_LINK_EDITOR");
	$PAGE->addScript("sitemap.js");
}

$tpl->set("FORM_ACTION", make_uri(false,false,false));
if (isset($_GET["list"])) $tpl->set("PAGE_TITLE", _("Edit Link List"));
else $tpl->set("PAGE_TITLE", _("Edit Text File"));

if (substr($file, 0, 9) == 'userdata/') {
	$p->Path(USER_DATA_PATH, substr($file, 9));
} else {
	$p->Path($relpath, $file);
}
$file = $p->get();

if (has_post()) {

	$data = POST("output");
	$ret = write_file($file, $data);

	if (! $ret) {
		$tpl->set("EDIT_ERROR", _("Cannot create file"));
		$tpl->set("ERROR_MESSAGE", 
		          spf_("Unable to create file %s.", $file));
		$tpl->set("FILE_TEXT", htmlentities($data));
	}
	
} else {

	if (file_exists($file)) {
		$data = implode("", file($file));	
	} else {
		$data = "";
		if (! GET('map')) {
			$tpl->set("EDIT_ERROR", _("Create new file"));
			$tpl->set("ERROR_MESSAGE", _("The selected file does not exist.  It will be created."));
		}
	}
}

$tpl->set("FILE_TEXT", htmlentities($data));
$tpl->set("FILE_PATH", $file);
$tpl->set("FILE_SIZE", file_exists($file)?filesize($file):0);
$tpl->set("FILE_URL", localpath_to_uri($file));	
$tpl->set("FILE", $file);

if (GET('map')) {
	$tpl->set("SITEMAP_MODE");
	$tpl->set("FORM_MESSAGE", spf_('This page will help you create a site map to display in the navigation bar at the top of your blog.  This file is stored under the name %s in the root directory of your weblog for a personal sitemap or in the %s installation directory for the system default.  This file in simply a series of <abbr title="Hypertext Markup Language">HTML</abbr> links, each on it\'s own line, which the template will process into a list.  If you require a more complicated menu bar, you will have to create a custom template.',
basename(SITEMAP_FILE), PACKAGE_NAME));
	$tpl->set("PAGE_TITLE", _("Create site map"));
	$tpl->unsetVar("FILE_SIZE");
	$tpl->unsetVar("FILE_URL");
	$tpl->unsetVar("FILE_PATH");
	$tpl->unsetVar("FILE");
	$tpl->unsetVar("FILE_URL");
}

if (! defined("BLOG_ROOT")) $blog = false;

$PAGE->title = _("Edit file");
$PAGE->addStylesheet("form.css");
$PAGE->display($tpl->process(), $blog);

?>
