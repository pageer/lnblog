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
require_once("lib/creators.php");

$ent = NewBlogEntry();
$blg = NewBlog();
$page = NewPage(&$ent);

$submit_id = "submit";
$preview_id = "preview";
$tpl = NewTemplate(BLOG_EDIT_TEMPLATE);
$tpl->set("PREV_ID", $preview_id);
$tpl->set("SUBMIT_ID", $submit_id);
$blg->exportVars($tpl);

$tpl->set("FORM_ACTION", current_file() );
$tpl->set("SUBJECT", $ent->subject);
$tpl->set("DATA", $ent->data);
$tpl->set("HAS_HTML", $ent->has_html);
$tpl->set("COMMENTS", $ent->allow_comment);

if (POST($submit_id)) {
	$ret = $blg->updateEntry();
	if ($ret == UPDATE_SUCCESS) redirect($blg->getURL());
	else $blg->errorEntry($ret, $tpl);
} elseif (POST($preview_id)) {
	$blg->previewEntry($tpl);
}

$body = $tpl->process();
$page->title = $blg->name." - New Entry";
$page->addStylesheet("form.css", "blogentry.css");
$page->addScript("editor.js");
$page->display($body, &$blg);
?>
