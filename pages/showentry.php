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
require_once("tb.php");

$entry_path = getcwd();
$ent = new BlogEntry($entry_path);
$blg = new Blog();
			
if (POST(COMMENT_POST_REMEMBER)) {
	if (POST(COMMENT_POST_NAME)) setcookie(COMMENT_POST_NAME, POST(COMMENT_POST_NAME));
	if (POST(COMMENT_POST_EMAIL)) setcookie(COMMENT_POST_EMAIL, POST(COMMENT_POST_EMAIL));
	if (POST(COMMENT_POST_URL)) setcookie(COMMENT_POST_URL, POST(COMMENT_POST_URL));
}

# This code will detect if a comment has been submitted and, if so,
# will add it.  We do this before printing the comments so that a 
# new comment will be displayed on the page.

$SUBMIT_ID = "submit";

$comm = new BlogComment;
if (POST($SUBMIT_ID)) {
	$ent->addComment(getcwd().PATH_DELIM.ENTRY_COMMENT_DIR);
}

# Get the entry AFTER posting the comment so that the comment count is right.
$title = $ent->subject . " - " . $blg->name;
$content =  $ent->get( $blg->canModifyEntry() );

# Array for adding style sheets.  We build this as we go so that we don't 
# end up sending more stylesheets than we need to.
$extra_styles = array("blogentry.css");

# Add TrackBacks for current entry.
$tmp_content = $ent->getTrackbacks();
$content .= $tmp_content;
if ($tmp_content) $extra_styles[] = "trackback.css";
# Now add the comments.
$tmp_content = $ent->getComments();
$content .= $tmp_content;
if ($tmp_content) $extra_styles[] = "comment.css";

# Add comment form if applicable.
if ($ent->allow_comment) { 
	$extra_styles[] = "form.css";
	$comm_tpl = new PHPTemplate;
	$comm_tpl->file = COMMENT_FORM_TEMPLATE;
	$comm_tpl->set("SUBMIT_ID", $SUBMIT_ID);
	$content .= $comm_tpl->process();
}

# Set up template for basic page layout.
$tpl = new PHPTemplate(BASIC_LAYOUT_TEMPLATE);
$blg->exportVars($tpl);
$tpl->set("PAGE_TITLE", $title);
$tpl->set("PAGE_CONTENT", $content);
$tpl->set("STYLE_SHEETS", $extra_styles);

# Set RSS feeds for comments, if we have any.
if (is_file(dirname($ent->file).PATH_DELIM.ENTRY_COMMENT_DIR.PATH_DELIM.COMMENT_RSS2_PATH)) {
	$tpl->set("ENTRY_RSS2_FEED", $ent->permalink().ENTRY_COMMENT_DIR."/".COMMENT_RSS2_PATH);
	$tpl->set("XML_FEED", $ent->permalink().ENTRY_COMMENT_DIR."/".COMMENT_RSS2_PATH);
	$tpl->set("XML_FEED_TITLE", "RSS 2.0 feed for comments on '".$ent->subject."'");
}
if (is_file(dirname($ent->file).PATH_DELIM.ENTRY_COMMENT_DIR.PATH_DELIM.COMMENT_RSS1_PATH)) {
	$tpl->set("ENTRY_RSS1_FEED", $ent->permalink().ENTRY_COMMENT_DIR."/".COMMENT_RSS1_PATH);
	$tpl->set("RDF_FEED", $ent->permalink().ENTRY_COMMENT_DIR."/".COMMENT_RSS1_PATH);
	$tpl->set("RDF_FEED_TITLE", "RSS 1.0 feed for comments on '".$ent->subject."'");
}

echo $tpl->process();
?>
