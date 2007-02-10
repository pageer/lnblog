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

# File: blogpaths.php
# Used to update the INSTALL_ROOT, INSTALL_ROOT_URL, and BLOG_ROOT_URL for a 
# particular blog.  This is basically an editor for the pathconfig.php file.

session_start();
require_once("config.php");
require_once("lib/creators.php");
require_once("lib/utils.php");

global $PAGE;

if (POST("blogpath")) $blog_path = POST("blogpath");
elseif (GET("blogpath")) $blog_path = GET("blogpath");
else $blog_path = false;

$blog = NewBlog($blog_path);
$usr = NewUser();
$tpl = NewTemplate("blog_path_tpl.php");
$PAGE->setDisplayObject($blog);

$inst_root = INSTALL_ROOT;
$inst_url = INSTALL_ROOT_URL;
$blog_url = $blog->getURL(); #BLOG_ROOT_URL;

if (! ($SYSTEM->canModify($blog, $usr) && $usr->checkLogin())  ) {
	$PAGE->redirect($blog->uri('login'));
	exit;
}

# NOTE - we should sanitize this input to avoid XSS attacks.  Then again, 
# since this page is not publicly accessible, is that needed?

if (has_post()) {
	$inst_root = POST("installroot");
	$inst_url = POST("installrooturl");
	#$blog_url = POST("blogrooturl");
	$ret = write_file(mkpath(BLOG_ROOT,"pathconfig.php"), 
	                  pathconfig_php_string($inst_root, $inst_url, $blog_url));
	if (!$ret) $tpl->set("UPDATE_MESSAGE", _("Error updating blog paths."));
	else {
		$PAGE->redirect($blog_url);
		exit;
	}
}

#$tpl->set("BLOG_URL", $blog_url);
$tpl->set("INST_URL", $inst_url);
$tpl->set("INST_ROOT", $inst_root);
$tpl->set("POST_PAGE", current_file());
$tpl->set("UPDATE_TITLE", sprintf(_("Update paths for %s"), $blog->name));

$body = $tpl->process();
$PAGE->title = sprintf(_("Update blog paths - %s"), htmlspecialchars($blog->name));
$PAGE->addStylesheet("form.css");
$PAGE->display($body, &$blog);
?>
