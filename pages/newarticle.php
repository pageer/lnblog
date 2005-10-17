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

# This file is used to display a blog entry with its comments.
# If you want to modify the layout of this page, you should be able to simply
# change the HTML code and leave the PHP code alone without seriously 
# breaking anything.

session_start();
require_once("lib/creators.php");

$blg = NewBlog();
$page = NewPage(&$blg);

$url = "art_url";
$submit_id = "submit";
$preview_id = "preview";
$tpl = NewTemplate(ARTICLE_EDIT_TEMPLATE);

$tpl->set("FORM_ACTION", current_file() );
$tpl->set("ARTICLE_POST_SUBJECT", ENTRY_POST_SUBJECT);
$tpl->set("ARTICLE_POST_DATA", ENTRY_POST_DATA);
$tpl->set("ARTICLE_POST_HTML", ENTRY_POST_HTML);
$tpl->set("ARTICLE_POST_COMMENTS", ENTRY_POST_COMMENTS);
$tpl->set("ARTICLE_POST_URL", $url);

# Disable comments by default.
$tpl->set("COMMENTS", 0);
$tpl->set("SUBMIT_ID", $submit_id);
$tpl->set("PREV_ID", $preview_id);
$tpl->set("HAS_HTML", MARKUP_BBCODE);
$blg->exportVars($tpl);

if (POST($submit_id)) {
	$ret = $blg->newArticle(POST($url));
	$last_art =& $blg->last_article;
	if ($ret == UPDATE_SUCCESS) $page->redirect($blg->last_article->permalink());
	else $blg->errorArticle($ret, $tpl);
} elseif (POST($preview_id)) {
	$tpl->set("URL", POST($url));
	$blg->previewArticle($tpl);
}

$body = $tpl->process();
$page->title = $blg->name." - New Article";
$page->addStylesheet("form.css", "article.css");
$page->addScript("editor.js");
$page->display($body, &$blg);
?>
