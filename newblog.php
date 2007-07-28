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
require_once("pages/pagelib.php");
require_once("lib/path.php");

global $PAGE;

if (POST("blogpath")) $path = POST("blogpath");
else $path = false;

$blog = NewBlog($path);
$usr = NewUser();
if (! $usr->isAdministrator() && $usr->checkLogin()) {
	$PAGE->redirect("bloglogin.php");
	exit;
}
$tpl = NewTemplate("blog_modify_tpl.php");
$blog->owner = $usr->username();

if (POST("blogpath")) $blog->home_path = POST("blogpath");
else $blog->home_path = "myblog";

if (has_post()) {

	$blog->owner = POST("owner");
	blog_get_post_data($blog);
}

$tpl->set("SHOW_BLOG_PATH");
$tpl->set("BLOG_PATH_REL", $blog->home_path);
$tpl->set("BLOG_OWNER", $blog->owner);
blog_set_template($tpl, $blog);
$tpl->set("BLOG_PATH", $blog->home_path);
$tpl->set("POST_PAGE", current_file());
$tpl->set("UPDATE_TITLE", _("Create new weblog"));

# If the user doesn't give us an absolute path, assume it's relative
# to the DOCUMENT_ROOT.  We put it down here so that the form data
# gets displayed as it was entered.
$p = new Path($blog->home_path);
if (! $p->isAbsolute($blog->home_path)) {
	$p->Path(calculate_document_root(),$blog->home_path);
	$blog->home_path = $p->get();
}

if ( has_post() ) {
	$ret = false;
	if (strcasecmp(realpath($blog->home_path), realpath(INSTALL_ROOT)) == 0) {
		$tpl->set("UPDATE_MESSAGE", spf_("The blog path you specified is the same as your %s installation path.  This is not allowed, as it will break your installation.  Please choose a different path for your blog.", PACKAGE_NAME));
	} else {
		$ret = $blog->insert();
		if ($ret) {
			$ret = $SYSTEM->registerBlog($blog->blogid);
			if ($ret) {
				$PAGE->redirect($blog->getURL());
			} else {
				$tpl->set("UPDATE_MESSAGE", _("Blog create but not registered.  This means the system will not list it on the admin pages.  Please try registering this blog by hand from the administration page."));
			}
		} else {
			$tpl->set("UPDATE_MESSAGE", _("Error creating blog.  This could be a problem with the file permissions on your server.  Please refer to the <a href=\"http://www.skepticats.com/LnBlog/documentation/\">documentation</a> for more information."));
		}
	}
}

$body = $tpl->process();
$PAGE->title = _("Create new blog");
$PAGE->addStylesheet("form.css");
$PAGE->display($body);
?>
