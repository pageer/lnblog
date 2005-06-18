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

session_start();
require_once("blogconfig.php");
require_once("blog.php");
require_once("user.php");

if (POST("blogpath")) $blog_path = POST("blogpath");
elseif (GET("blogpath")) $blog_path = GET("blogpath");
else $blog_path = false;

$blog = new Blog($blog_path);
$usr = new User;
$tpl = new PHPTemplate(BLOG_UPDATE_TEMPLATE);
$blog->exportVars($tpl);

if (! $blog->canModifyBlog() ) {
	refresh("login.php");
	exit;
}

$blogowner = "blogowner";
$blogname = "blogname";
$blogdesc = "desc";
$blogimage = "image";
$blogtheme = "theme";
$blogmax = "maxent";
$blogrss = "maxrss";
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
$tpl->set("POST_PAGE", current_file());
$tpl->set("SUBMIT_ID", $submitid);
$tpl->set("UPDATE_TITLE", "Update ".$blog->name);

# NOTE: sanitize this input to avoid XSS attacks.

# Only the site administrator can change a blog owner.
if ($usr->username() == ADMIN_USER && POST($blogowner) ) {
	$blog->owner = POST($blogowner);
}
if (POST($blogname)) $blog->name = POST($blogname);
if (POST($blogwriters)) $blog->writers(POST($blogwriters) );
if (POST($blogdesc)) $blog->description = POST($blogdesc);
if (POST($blogimage)) $blog->image = POST($blogimage);
if (POST($blogtheme)) $blog->theme = POST($blogtheme);
if (POST($blogmax)) $blog->max_entries = POST($blogmax);
if (POST($blogmax)) $blog->max_rss = POST($blogrss);

if (POST("submit")) {
	$ret = $blog->update();
	if (!$ret) $tpl->set("UPDATE_MESSAGE", "Error updating blog.");
	else refresh($blog->getURL());
}

$body = $tpl->process();
$tpl->file = BASIC_LAYOUT_TEMPLATE;
$tpl->set("PAGE_CONTENT", $body);
$tpl->set("PAGE_TITLE", "Update blog - ".$blog->name);
$tpl->set("STYLE_SHEETS", array("form.css") );
echo $tpl->process();
?>
