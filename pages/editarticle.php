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
require_once("blog.php");
require_once("article.php");

$ent = new Article( getcwd() );
$blg = new Blog();

$submit_id = "submit";
$preview_id = "preview";
$tpl = new PHPTemplate(ARTICLE_EDIT_TEMPLATE);
$tpl->set("SUBMIT_ID", $submit_id);
$tpl->set("PREV_ID", $preview_id);
$blg->exportVars($tpl);

$tpl->set("ARTICLE_POST_SUBJECT", ENTRY_POST_SUBJECT);
$tpl->set("ARTICLE_POST_DATA", ENTRY_POST_DATA);
$tpl->set("ARTICLE_POST_COMMENTS", ENTRY_POST_COMMENTS);
$tpl->set("ARTICLE_POST_HTML", ENTRY_POST_HTML);

$tpl->set("FORM_ACTION", current_file() );
$tpl->set("SUBJECT", $ent->subject);
$tpl->set("DATA", $ent->data);
$tpl->set("COMMENTS", $ent->allow_comment);
$tpl->set("HAS_HTML", $ent->has_html);

if ( has_post() ) {
	
	# Update the template variables with the new values.
	$ent->getPostData();
	$tpl->set("SUBJECT", $ent->subject);
	$tpl->set("DATA", $ent->data);
	$tpl->set("COMMENTS", $ent->allow_comment);
	$tpl->set("HAS_HTML", $ent->has_html);
	
	if ($ent->data) {
		if (POST($submit_id)) {
			$ent->update();
			redirect($ent->permalink());
		} else {
			$tpl->set("PREVIEW_DATA", $ent->get() );
		}
	} else {
		$tpl->set("HAS_UPDATE_ERROR");
		$tpl->set("UPDATE_ERROR_MESSAGE", "Error updating blog entry.");
	}
}
$body = $tpl->process();
$tpl->file = BASIC_LAYOUT_TEMPLATE;
$tpl->set("PAGE_CONTENT", $body);
$tpl->set("PAGE_TITLE", $blg->name." - New Entry");
$styles = array(THEME_STYLES."/main.css", THEME_STYLES."/blog.css", THEME_STYLES."/form.css");
$tpl->set("STYLE_SHEETS", $styles);
echo $tpl->process();
?>
