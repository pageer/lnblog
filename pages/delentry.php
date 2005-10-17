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
require_once("config.php");
require_once("lib/creators.php");

$blog = NewBlog();
$ent = NewBlogEntry();
$page = NewPage(&$ent);

$conf_id = "ok";
$cancel_id = "cancel";
$message = "Remove the weblog entry \"".$ent->subject."\"?";

if (POST($conf_id)) {
	$ret = $blog->deleteEntry();
	if ($ret == UPDATE_SUCCESS) $page->redirect(BLOG_ROOT_URL);
	else $message = "Unable to delete \"".$ent->subject."\".  Try again?";
} elseif (POST($cancel_id)) {
	redirect("index.php");
}

$tpl = NewTemplate(CONFIRM_TEMPLATE);
$tpl->set("CONFIRM_TITLE", "Remove entry?");
$tpl->set("CONFIRM_MESSAGE",$message);
$tpl->set("CONFIRM_PAGE", current_file() );
$tpl->set("OK_ID", $conf_id);
$tpl->set("OK_LABEL", "Yes");
$tpl->set("CANCEL_ID", "cancel");
$tpl->set("CANCEL_LABEL", "No");

$body = $tpl->process();

$page->title = $blog->name." - Delete comment";
$page->display($body, &$blog);

?>
