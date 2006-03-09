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

# File: newentry.php
# Used to create a new blog entry.  
# To edit or delete an entry, refer to the <editentry.php> and <delentry.php>
# files respectively.
#
# This is included by the new.php wrapper script for blogs.

session_start();
require_once("lib/creators.php");

$blg = NewBlog();
$page = NewPage(&$blg);

$submit_id = "submit";
$preview_id = "preview";
$tpl = NewTemplate(ENTRY_EDIT_TEMPLATE);
$tpl->set("FORM_ACTION", current_file() );
$tpl->set("SUBMIT_ID", $submit_id);
$tpl->set("PREV_ID", $preview_id);
$tpl->set("HAS_HTML", $blg->default_markup);
$blg->exportVars($tpl);

if (POST($submit_id)) {
	$ret = $blg->newEntry();
	if ($ret == UPDATE_SUCCESS) $page->redirect($blg->getURL());
	else $blg->errorEntry($ret, $tpl);
} elseif (POST($preview_id)) {
	$u = NewUser();
	$u->exportVars($tpl);
	$blg->previewEntry($tpl);
}

$body = $tpl->process();
$page->title = spf_("%s - New Entry", $blg->name);
$page->addStylesheet("form.css", "blogentry.css");
$page->addScript("editor.js");
$page->display($body, &$blg);
?>
