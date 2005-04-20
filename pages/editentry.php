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
require_once("blogentry.php");
require_once("rss.php");

$entry_path = getcwd();
$ent = new BlogEntry($entry_path);
$blg = new Blog();

$submit_id = "submit";
$preview_id = "preview";
$tpl = new PHPTemplate(BLOG_EDIT_TEMPLATE);
$tpl->set("PREV_ID", $preview_id);
$tpl->set("SUBMIT_ID", $submit_id);
$blg->exportVars($tpl);

$tpl->set("FORM_ACTION", current_file() );
$tpl->set("SUBJECT", $ent->subject);
$tpl->set("DATA", $ent->data);
$tpl->set("HAS_HTML", $ent->has_html);
$tpl->set("COMMENTS", $ent->allow_comment);

if ( has_post() ) {
	
	# Reset template variables to new data from form.
	$ent->getPostData();
	$tpl->set("SUBJECT", $ent->subject);
	$tpl->set("DATA", $ent->data);
	$tpl->set("HAS_HTML", $ent->has_html);
	$tpl->set("COMMENTS", $ent->allow_comment);
	
	if ($ent->data) {
		if (POST($submit_id)) {
			$ent->update();
			#$year = date("Y", $ent->timestamp);
			#$month = date("m", $ent->timestamp);
			#$blg->createPath($year, $month);
			$blg->updateRSS1();
			$blg->updateRSS2();
			redirect($blg->getURL());
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
echo $tpl->process();
?>
