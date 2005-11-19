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

# File: editarticle.php
# Used to edit the content of an existing article.
# To create a new article, refer to the <newarticle.php> file.
#
# This is included by the edit.php wrapper script for articles.

session_start();
require_once("lib/creators.php");

$ent = NewArticle();
$blg = NewBlog();
$page = NewPage(&$ent);

$submit_id = _("Submit");
$preview_id = _("Preview");
$tpl = NewTemplate(ENTRY_EDIT_TEMPLATE);
$tpl->set("SUBMIT_ID", $submit_id);
$tpl->set("PREV_ID", $preview_id);
$blg->exportVars($tpl);

$tpl->set("FORM_ACTION", current_file() );
$tpl->set("SUBJECT", $ent->subject);
$tpl->set("DATA", $ent->data);
$tpl->set("COMMENTS", $ent->allow_comment);
$tpl->set("HAS_HTML", $ent->has_html);

if (POST($submit_id)) {
	$ret = $blg->updateArticle();
	if ($ret == UPDATE_SUCCESS) $page->redirect($ent->permalink());
	else $blg->errorArticle($ret, $tpl);
} elseif (POST($preview_id)) {
	$blg->previewArticle($tpl);
}	

$body = $tpl->process();
$page->title = spf_("%s - Edit article", $blg->name);
$page->addStylesheet("form.css", "article.css");
$page->addScript("editor.js");
$page->display($body, &$blog);
?>
