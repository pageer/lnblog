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
require_once("blog.php");
require_once("blogentry.php");
require_once("blogcomment.php");

$blog = new Blog();
$comm = new BlogComment();

if (! $blog->canModifyEntry() ) redirect("index.php");

$comm_id = "comment";

$anchor = POST($comm_id);
if (!$anchor) $anchor = GET($comm_id);
if (!$anchor) redirect("index.php");

$comm->file = $comm->getFilename($anchor);
$comm->readFileData();

$conf_id = "ok";
$conf_val = "Yes";
$message = "Do you really want to delete ".$anchor."?";

if (POST($conf_id)) {
	$ret = $comm->delete();
	if ($ret) redirect("index.php");
	else $message = "Unable to delete ".$anchor.".  Try again?";
}

$tpl = new PHPTemplate(CONFIRM_TEMPLATE);
$tpl->set("CONFIRM_TITLE", "Delete comment?");
$tpl->set("CONFIRM_MESSAGE",$message);
$tpl->set("CONFIRM_PAGE", current_file() );
$tpl->set("OK_ID", $conf_id);
$tpl->set("OK_LABEL", $conf_val);
$tpl->set("CANCEL_ID", "cancel");
$tpl->set("CANCEL_LABEL", "Cancel");
$tpl->set("PASS_DATA_ID", $comm_id);
$tpl->set("PASS_DATA", $anchor);

$body = $tpl->process();

$tpl->file = BASIC_LAYOUT_TEMPLATE;
$blog->exportVars($tpl);
$tpl->set("PAGE_TITLE", $blog->name." - Delete comment");
$tpl->set("PAGE_CONTENT", $body);

echo $tpl->process();
?>
