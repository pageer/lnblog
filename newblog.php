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
#ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.getcwd().PATH_DELIM."templates");
require_once("blog.php");

if (! check_login() ) {
	refresh("login.php");
	exit;
}

$blogpath = "blogpath";
$blogname = "blogname";
$blogdesc = "desc";
$blogimage = "image";
$blogmax = "maxent";
$blogpath = "blogpath";
$submitid = "submit";

if (POST($blogpath)) $path = POST($blogpath);
else $path = false;

$blog = new Blog($path);
$tpl = new PHPTemplate(BLOG_UPDATE_TEMPLATE);

if (POST($blogpath)) $blog->home_path = POST($blogpath);
else $blog->home_path = "../myblog";
if (POST($blogname)) $blog->name = POST($blogname);
if (POST($blogdesc)) $blog->description = POST($blogdesc);
if (POST($blogimage)) $blog->image = POST($blogimage);
if (POST($blogmax)) $blog->max_entries = POST($blogmax);

$tpl->set("BLOG_PATH_ID", $blogpath);
$tpl->set("BLOG_PATH_REL", $blog->home_path);
$tpl->set("BLOG_NAME_ID", $blogname);
$tpl->set("BLOG_NAME", $blog->name);
$tpl->set("BLOG_DESC_ID", $blogdesc);
$tpl->set("BLOG_DESC", $blog->description);
$tpl->set("BLOG_IMAGE_ID", $blogimage);
$tpl->set("BLOG_IMAGE", $blog->image);
$tpl->set("BLOG_MAX_ID", $blogmax);
$tpl->set("BLOG_MAX", $blog->max_entries);
$tpl->set("BLOG_PATH_ID", $blogpath);
$tpl->set("BLOG_PATH", $blog->home_path);
$tpl->set("POST_PAGE", current_file());
$tpl->set("SUBMIT_ID", $submitid);
$tpl->set("UPDATE_TITLE", "Create new weblog");

if (POST("submit")) {
	$ret = $blog->insert();
	if (!$ret) $tpl->set("UPDATE_MESSAGE", "Error creating blog.");
	else refresh($blog->getURL());
}

$blog->exportVars($tpl);
$body = $tpl->process();
$tpl->file = BASIC_LAYOUT_TEMPLATE;
$tpl->set("PAGE_CONTENT", $body);
$tpl->set("PAGE_TITLE", "Create new blog");
echo $tpl->process();
?>
