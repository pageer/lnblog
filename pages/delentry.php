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

# File: delentry.php
# Used to delete a blog entry.
# Entries are created and modified with the <newentry.php> and 
# <editentry.php> files respectively.
#
# This is included by the delete.php wrapper script for blog entries.

session_start();
require_once("config.php");
require_once("lib/creators.php");

$blog = NewBlog();
$ent = NewBlogEntry();
$page = NewPage(&$ent);

$conf_id = _("OK");
$cancel_id = _("Cancel");
$message = spf_("Do you really want to delete '%s'?", $ent->subject);

if (POST($conf_id)) {
	$ret = $blog->deleteEntry();
	if ($ret == UPDATE_SUCCESS) $page->redirect(BLOG_ROOT_URL);
	else $message = spf_("Unable to delete '%s'.  Try again?", $ent->subject);
} elseif (POST($cancel_id)) {
	$page->redirect("index.php");
}

$tpl = NewTemplate(CONFIRM_TEMPLATE);
$tpl->set("CONFIRM_TITLE", _("Remove entry?"));
$tpl->set("CONFIRM_MESSAGE",$message);
$tpl->set("CONFIRM_PAGE", current_file() );
$tpl->set("OK_ID", $conf_id);
$tpl->set("OK_LABEL", _("Yes"));
$tpl->set("CANCEL_ID", $cancel_id);
$tpl->set("CANCEL_LABEL", _("No"));

$body = $tpl->process();

$page->title = spf_("%s - Delete entry", $blog->name);
$page->display($body, &$blog);

?>
