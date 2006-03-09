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

# File: updateblog.php
# Used to update the settings on an existing blog.
#
# In the standard setup, this file is included by the per-blog edit.php file.

session_start();
require_once("blogconfig.php");
require_once("lib/creators.php");
require_once("lib/utils.php");

if (POST("blogpath")) $blog_path = POST("blogpath");
elseif (GET("blogpath")) $blog_path = GET("blogpath");
else $blog_path = false;

$blog = NewBlog($blog_path);
$usr = NewUser();
$tpl = NewTemplate(BLOG_UPDATE_TEMPLATE);
$page = NewPage(&$blog);

if (! $blog->canModifyBlog() ) {
	$page->redirect("login.php");
	exit;
}

$blogowner = "blogowner";
$blogname = "blogname";
$blogdesc = "desc";
$blogimage = "image";
$blogtheme = "theme";
$blogmax = "maxent";
$blogrss = "maxrss";
$blogmarkup = "blogmarkup";
$blogwriters = "writelist";
$submitid = "submit";

# Shouldn't this go _after_ the blog object is updated?

if ($usr->username() == ADMIN_USER) {
	$tpl->set("BLOG_OWNER_ID", $blogowner);
	$tpl->set("BLOG_OWNER", $blog->owner);
}
$tpl->set("BLOG_NAME_ID", $blogname);
$tpl->set("BLOG_NAME", $blog->name);
$tpl->set("BLOG_WRITERS_ID", $blogwriters);
$tpl->set("BLOG_WRITERS", implode(",", $blog->writers() ) );
$tpl->set("BLOG_DESC_ID", $blogdesc);
$tpl->set("BLOG_DESC", $blog->description);
$tpl->set("BLOG_IMAGE_ID", $blogimage);
$tpl->set("BLOG_IMAGE", $blog->image);
$tpl->set("BLOG_THEME_ID", $blogtheme);
$tpl->set("BLOG_THEME", $blog->theme);
$tpl->set("BLOG_MAX_ID", $blogmax);
$tpl->set("BLOG_MAX", $blog->max_entries);
$tpl->set("BLOG_RSS_MAX_ID", $blogrss);
$tpl->set("BLOG_RSS_MAX", $blog->max_rss);
$tpl->set("BLOG_DEFAULT_MARKUP_ID", $blogmarkup);
$tpl->set("BLOG_DEFAULT_MARKUP", $blog->default_markup);
$tpl->set("POST_PAGE", current_file());
$tpl->set("SUBMIT_ID", $submitid);
$tpl->set("UPDATE_TITLE", sprintf(_("Update %s"), $blog->name));

# NOTE - sanitize this input to avoid XSS attacks.

# Only the site administrator can change a blog owner.
if ($usr->username() == ADMIN_USER && POST($blogowner) ) {
	$blog->owner = POST($blogowner);
}
if (has_post()) {
	$blog->name = POST($blogname);
	$blog->writers(POST($blogwriters) );
	$blog->description = POST($blogdesc);
	$blog->image = POST($blogimage);
	$blog->theme = POST($blogtheme);
	$blog->max_entries = POST($blogmax);
	$blog->max_rss = POST($blogrss);
	$blog->default_markup = POST($blogmarkup);
}

if (POST("submit")) {
	$ret = $blog->update();
	if (!$ret) $tpl->set("UPDATE_MESSAGE", _("Error updating blog."));
	else $page->redirect($blog->getURL());
}

$body = $tpl->process();
$page->title = sprintf(_("Update blog - %s"), $blog->name);
$page->addStylesheet("form.css");
$page->display($body, &$blog);
?>
