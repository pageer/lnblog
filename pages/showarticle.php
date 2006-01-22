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

# File: showarticle.php
# Displays an article with any associated comments and TrackBacks.
#
# This is included in the index.php wrapper script of articles.

session_start();
require_once("config.php");
require_once("lib/creators.php");

$ent = NewArticle();
$blg = NewBlog();
$page = NewPage(&$ent);

$SUBMIT_ID = "submit";

if (POST($SUBMIT_ID)) {
	$cmt_added = $ent->addComment(getcwd().PATH_DELIM.ENTRY_COMMENT_DIR);
	if ($cmt_added) {
		# We add the random template here so that the "remember me" cookies
		# are set.  This should definitely be fixed.
		$t = NewTemplate("comment_form_tpl.php");
		$ret = $t->process();
		$page->redirect(current_file());
	}
}

$title = $ent->subject . " - " . $blg->name;
$content =  $ent->get( $blg->canModifyEntry() );

# Extra styles - don't include more than we need to render the page.
$page->addStylesheet("article.css");

# Add comments for current entry.
$tmp_content = $ent->getComments(); 
$content .= $tmp_content;
if ($tmp_content) $page->addStylesheet("comment.css");

# Add comment form if applicable.
if ($ent->allow_comment) { 
	$page->addStylesheet("form.css");
	$comm_tpl = NewTemplate();
	$comm_tpl->file = COMMENT_FORM_TEMPLATE;
	$comm_tpl->set("SUBMIT_ID", $SUBMIT_ID);
	$content .= $comm_tpl->process();
}

# Set up template for basic page layout.
$page->addScript("entry.js");
$page->title = $title;
$page->display($content, &$blog);

?>
