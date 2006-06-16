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

# File: delcomment.php
# Used to delete a reader comment.
# Comments are added to entries and articles in the <showentry.php> and
# <showcomments.php> files respectively.
#
# This is included by the delete.php wrapper script in the comments 
# subdirectory of entries and articles.

session_start();
require_once("config.php");
require_once("lib/creators.php");

global $PAGE;

$blog = NewBlog();
$u = NewUser();

$anchor = POST("comment");
if (!$anchor) $anchor = GET("comment");
if (!$anchor) $PAGE->redirect("index.php");
$comm = NewBlogComment($anchor);
$PAGE->setDisplayObject($comm);

if (! $SYSTEM->canDelete($comm, $u) || ! $u->checkLogin() ) {
	$PAGE->redirect("index.php");
	exit;
}

$conf_id = _("OK");
$message = spf_("Do you really want to delete %s?", $anchor);

if (POST($conf_id) || GET("conf") == "yes") {
	$ret = $comm->delete();
	if ($ret) $PAGE->redirect("index.php");
	else $message = spf_("Unable to delete %s.  Try again?", $anchor);
}

$tpl = NewTemplate(CONFIRM_TEMPLATE);
$tpl->set("CONFIRM_TITLE", _("Delete comment?"));
$tpl->set("CONFIRM_MESSAGE",$message);
$tpl->set("CONFIRM_PAGE", current_file() );
$tpl->set("OK_ID", $conf_id);
$tpl->set("OK_LABEL", _("Yes"));
$tpl->set("CANCEL_ID", _("Cancel"));
$tpl->set("CANCEL_LABEL", _("Cancel"));
$tpl->set("PASS_DATA_ID", "comment");
$tpl->set("PASS_DATA", $anchor);
$body = $tpl->process();

$PAGE->title = spf_("%s - Delete comment", $blog->name);
$PAGE->display($body, $blog);

?>
