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

# File: newblog.php
# Used to create a new weblog.
#
# The form is initially populated with a reasonable list of default values,
# taken from <blogconfig.php>.  The user will probably want to change the 
# path and will need to set a name and description.  The blog owner
# may also need to be changed and the list of allowed writers may need to 
# be set as well.

session_start();
require_once("blogconfig.php");
require_once("lib/creators.php");

$page = NewPage();

if (POST("blogpath")) $path = POST("blogpath");
else $path = false;

$blog = NewBlog($path);
if (! $blog->canAddBlog() ) {
	$page->redirect("bloglogin.php");
	exit;
}
$tpl = NewTemplate("blog_modify_tpl.php");

if (POST("blogpath")) $blog->home_path = POST("blogpath");
else $blog->home_path = "myblog";

if (has_post()) {

	$blog->owner = POST("owner");
	$blog->name = POST("blogname");
	$blog->writers(POST("writelist") );
	$blog->description = POST("desc");
	$blog->image = POST("image");
	$blog->theme = POST("theme");
	$blog->max_entries = POST("maxent");
	$blog->max_rss = POST("maxrss");
	$blog->default_markup = POST("blogmarkup");
}

$tpl->set("SHOW_BLOG_PATH");
$tpl->set("BLOG_PATH_REL", $blog->home_path);
$tpl->set("BLOG_OWNER", $blog->owner);
$tpl->set("BLOG_WRITERS", implode(",", $blog->writers()) );
$tpl->set("BLOG_NAME", $blog->name);
$tpl->set("BLOG_DESC", $blog->description);
$tpl->set("BLOG_IMAGE", $blog->image);
$tpl->set("BLOG_THEME", $blog->theme);
$tpl->set("BLOG_MAX", $blog->max_entries);
$tpl->set("BLOG_RSS_MAX", $blog->max_rss);
$tpl->set("BLOG_DEFAULT_MARKUP", $blog->default_markup);
$tpl->set("BLOG_PATH", $blog->home_path);
$tpl->set("POST_PAGE", current_file());
$tpl->set("UPDATE_TITLE", _("Create new weblog"));

# If the user doesn't give us an absolute path, assume it's relative
# to the DOCUMENT_ROOT.  We put it down here so that the form data
# gets displayed as it was entered.
if (! is_absolute($blog->home_path)) {
	$blog->home_path = calculate_document_root().PATH_DELIM.$blog->home_path;
}

if ( has_post() ) {
	$ret = false;
	if (strcasecmp(realpath($blog->home_path), realpath(INSTALL_ROOT)) == 0) {
		$tpl->set("UPDATE_MESSAGE", spf_("The blog path you specified is the same as your %s installation path.  This is not allowed, as it will break your installation.  Please choose a different path for your blog.", PACKAGE_NAME));
	} else {
		$ret = $blog->insert();
		if (!$ret) $tpl->set("UPDATE_MESSAGE", _("Error creating blog.  This could be a problem with the file permissions on your server.  Please refer to the <a href=\"http://www.skepticats.com/LnBlog/Readme.html\">documentation</a> for more information."));
		else $page->redirect($blog->getURL());
	}
}

$body = $tpl->process();
$page->title = _("Create new blog");
$page->addStylesheet("form.css");
$page->display($body);
?>
