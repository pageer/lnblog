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
require_once("lib/creators.php");

$blog = NewBlog();
$page = NewPage(&$blog);

$cancel_id = "cancel";
$cancel_label = "No";
$ok_id = "ok";
$ok_label = "Yes";

$tpl = NewTemplate(CONFIRM_TEMPLATE);
$tpl->set("CONFIRM_TITLE", "Logout");
$tpl->set("CONFIRM_MESSAGE", "Do you really want to log out?");
$tpl->set("CONFIRM_PAGE", current_file());
$tpl->set("OK_ID", $ok_id);
$tpl->set("OK_LABEL", $ok_label);
$tpl->set("CANCEL_ID", $cancel_id);
$tpl->set("CANCEL_LABEL", $cancel_label);

if (POST($ok_id)) {
	$usr = NewUser();
	$usr->logout();
	$page->redirect($blog->getURL());
} else if (POST($cancel_id)) {
	$page->redirect($blog->getURL());
}

$body = $tpl->process();
$page->title = $blog->name." - Logout";
$page->display($body, &$blog);

?>
