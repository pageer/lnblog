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
# Note that some of the settings on this page, such as the settings to turn on
# and off comments, TrackBacks, etc., are default settings, not global settings.
# They are not applied retroactively to old entries and can be changed when
# adding a new entry.  To globally disable comments, TrackBacks, and Pignbacks
# for a blog, use the <disable_comments.php> plugin.
#
# In the standard setup, this file is included by the per-blog edit.php file.

session_start();
require_once("config.php");
require_once("lib/creators.php");
require_once("lib/utils.php");
require_once("pages/pagelib.php");

global $PAGE;

if (POST("blogpath")) $blog_path = POST("blogpath");
elseif (GET("blogpath")) $blog_path = GET("blogpath");
else $blog_path = false;

$blog = NewBlog($blog_path);
$usr = NewUser();
$tpl = NewTemplate("blog_modify_tpl.php");
$PAGE->setDisplayObject($blog);

# NOTE - we should sanitize this input to avoid XSS attacks.  Then again, 
# since this page is not publicly accessible, is that needed?

if (has_post()) {
	
	if ($SYSTEM->canModify($blog, $usr) && $usr->checkLogin()) {
		# Only the site administrator can change a blog owner.
		if ($usr->username() == ADMIN_USER && POST("blogowner") ) {
			$blog->owner = POST("blogowner");
		}
		blog_get_post_data($blog);
	
		$ret = $blog->update();
		$SYSTEM->registerBlog($blog->blogid);
		if (!$ret) $tpl->set("UPDATE_MESSAGE", _("Error: unable to update blog."));
		else $PAGE->redirect($blog->getURL());
	} else {
		$tpl->set("UPDATE_MESSAGE", _("Error: user %s"));
	}
}

if ($usr->username() == ADMIN_USER) {
	$tpl->set("BLOG_OWNER", $blog->owner);
}
blog_set_template($tpl, $blog);
$tpl->set("POST_PAGE", current_file());
$tpl->set("UPDATE_TITLE", sprintf(_("Update %s"), $blog->name));

$body = $tpl->process();
$PAGE->title = spf_("Update blog - %s", $blog->name);
$PAGE->addStylesheet("form.css");
$PAGE->display($body, &$blog);
?>
