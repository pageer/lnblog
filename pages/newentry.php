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
require_once("rss.php");
require_once("blog.php");
require_once("blogentry.php");

$entry_path = getcwd();
$ent = new BlogEntry;
$blg = new Blog();

$submit_id = "submit";
$preview_id = "preview";
$tpl = new PHPTemplate(BLOG_EDIT_TEMPLATE);
$tpl->set("FORM_ACTION", current_file() );
$tpl->set("SUBMIT_ID", $submit_id);
$tpl->set("PREV_ID", $preview_id);
$tpl->set("HAS_HTML", MARKUP_BBCODE);
$blg->exportVars($tpl);

$has_error = "";
if ( has_post() ) {
	
	# Set data recieved from form to template variables.
	$ent->getPostData();
	$tpl->set("PREVIEW_DATA", $ent->get() );
	$tpl->set("SUBJECT", $ent->subject);
	$tpl->set("DATA", $ent->data);
	$tpl->set("HAS_HTML", $ent->has_html);
	
	if ($ent->data) {
		if (POST($submit_id)) {
			$ent->insert();
			$blg->updateRSS1();
			$blg->updateRSS2();
			redirect($blg->getURL());
		}
	} else {
		$tpl->set("HAS_UPDATE_ERROR");
		$tpl->set("UPDATE_ERROR_MESSAGE", "Entry has no data.");
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
