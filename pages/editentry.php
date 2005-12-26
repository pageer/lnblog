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

# File: editentry.php
# Used to edit existing blog entries.
# To create a new blog entry, refer to the <newentry.php> file.
#
# This is included by the edit.php wrapper script for blog entries.

session_start();
require_once("lib/creators.php");

$ent = NewBlogEntry();
$blg = NewBlog();
$page = NewPage(&$ent);

$submit_id = _("Submit");
$preview_id = _("Preview");
$tpl = NewTemplate(ENTRY_EDIT_TEMPLATE);
$tpl->set("PREV_ID", $preview_id);
$tpl->set("SUBMIT_ID", $submit_id);
$blg->exportVars($tpl);

$tpl->set("FORM_ACTION", current_file() );
$tpl->set("SUBJECT", $ent->subject);
$tpl->set("TAGS", $ent->tags);
$tpl->set("DATA", $ent->data);
$tpl->set("HAS_HTML", $ent->has_html);
$tpl->set("COMMENTS", $ent->allow_comment);

if (POST($submit_id)) {
	$ret = $blg->updateEntry();
	if ($ret == UPDATE_SUCCESS) $page->redirect($blg->getURL());
	else $blg->errorEntry($ret, $tpl);
} elseif (POST($preview_id)) {
	$blg->previewEntry($tpl);
}

$body = $tpl->process();
$page->title = spf_("%s - Edit entry", $blg->name);
$page->addStylesheet("form.css", "blogentry.css");
$page->addScript("editor.js");
$page->display($body, &$blg);
?>
