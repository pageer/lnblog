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
require_once("blog.php");
require_once("article.php");

if (! check_login() ) redirect("index.php");

$ent = new Article;
$blg = new Blog();

$subject = "subject";
$path = "path";
$data = "data";
$html = "html";
$comments = "comments";
$url = "art_url";

$submit_id = "submit";
$tpl = new PHPTemplate(ARTICLE_EDIT_TEMPLATE);

$tpl->set("FORM_ACTION", current_file() );
$tpl->set("ARTICLE_POST_SUBJECT", $subject);
$tpl->set("ARTICLE_POST_PATH", $path);
$tpl->set("ARTICLE_POST_DATA", $data);
$tpl->set("ARTICLE_POST_HTML", $html);
$tpl->set("ARTICLE_POST_COMMENTS", $comments);
$tpl->set("ARTICLE_POST_URL", $url);

# Disable comments by default.
$tpl->set("COMMENTS", 0);
$tpl->set("SUBMIT_ID", $submit_id);
$tpl->set("HAS_HTML", MARKUP_BBCODE);
$blg->exportVars($tpl);

$has_error = "";
if (POST($submit_id)) {
	
	$tpl->set("SUBJECT", POST($subject) );
	$tpl->set("URL", POST($url) );
	$tpl->set("DATA", POST($data) );
	$tpl->set("HAS_HTML", POST($html) );

	$ent->getPostData();
	if ($ent->data) {
		$ent->insert(trim(POST($url)));
		redirect($ent->permalink());
	} else {
		$tpl->set("HAS_UPDATE_ERROR");
		$tpl->set("UPDATE_ERROR_MESSAGE", "Error creating article.");
	}
}

$body = $tpl->process();
$tpl->file = BASIC_LAYOUT_TEMPLATE;
$tpl->set("PAGE_CONTENT", $body);
$tpl->set("PAGE_TITLE", $blg->name." - New Article");
echo $tpl->process();
?>
