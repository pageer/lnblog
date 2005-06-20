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
require_once("rss.php");

$SUBMIT_ID = "submit";

$entry_path = dirname(getcwd());
$ent = new BlogEntry($entry_path);
$blg = new Blog();
			
if (! empty($_POST[COMMENT_POST_REMEMBER]) ) {
	if (!empty($_POST[COMMENT_POST_NAME])) setcookie(COMMENT_POST_NAME, $_POST[COMMENT_POST_NAME]);
	if (!empty($_POST[COMMENT_POST_EMAIL])) setcookie(COMMENT_POST_EMAIL, $_POST[COMMENT_POST_EMAIL]);
	if (!empty($_POST[COMMENT_POST_URL])) setcookie(COMMENT_POST_URL, $_POST[COMMENT_POST_URL]);
}

$title = $ent->subject . " - " . $blg->name;

# This code will detect if a comment has been submitted and, if so,
# will add it.  We do this before printing the comments so that a 
# new comment will be displayed on the page.
$comm = new BlogComment;
if (POST($SUBMIT_ID)) {
	$ent->addComment(getcwd());
}

$content = $ent->getComments(); 

# Extra styles to add.  Build the list as we go to keep from including more
# style sheets than we need to.
$extra_styles = array("comment.css");

if ($ent->allow_comment) { 
	$extra_styles[] = "form.css";
	$comm_tpl = new PHPTemplate;
	$comm_tpl->file = COMMENT_FORM_TEMPLATE;
	$comm_tpl->set("SUBMIT_ID", "submit");
	$content .= $comm_tpl->process();
}

$tpl = new PHPTemplate(BASIC_LAYOUT_TEMPLATE);
$blg->exportVars($tpl);
$tpl->set("PAGE_TITLE", $title);
$tpl->set("PAGE_CONTENT", $content);
$tpl->set("STYLE_SHEETS", $extra_styles);

# Set RSS feeds for comments, if we have any.
if (is_file($entry_path.PATH_DELIM.ENTRY_COMMENT_DIR.PATH_DELIM.COMMENT_RSS2_PATH)) {
	$tpl->set("ENTRY_RSS2_FEED", $ent->permalink().ENTRY_COMMENT_DIR."/".COMMENT_RSS2_PATH);
	$tpl->set("XML_FEED", $ent->permalink().ENTRY_COMMENT_DIR."/".COMMENT_RSS2_PATH);
	$tpl->set("XML_FEED_TITLE", "RSS 2.0 feed for comments on '".$ent->subject."'");
}
if (is_file($entry_path.PATH_DELIM.ENTRY_COMMENT_DIR.PATH_DELIM.COMMENT_RSS1_PATH)) {
	$tpl->set("ENTRY_RSS1_FEED", $ent->permalink().ENTRY_COMMENT_DIR."/".COMMENT_RSS1_PATH);
	$tpl->set("RDF_FEED", $ent->permalink().ENTRY_COMMENT_DIR."/".COMMENT_RSS1_PATH);
	$tpl->set("RDF_FEED_TITLE", "RSS 1.0 feed for comments on '".$ent->subject."'");
}

echo $tpl->process();
?>
