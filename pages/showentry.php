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
require_once("lib/creators.php");

$ent = NewBlogEntry();
$blg = NewBlog();
$page = NewPage(&$ent);
			
# This code will detect if a comment has been submitted and, if so,
# will add it.  We do this before printing the comments so that a 
# new comment will be displayed on the page.

if (has_post()) {
	$ret = $ent->addComment();
	if ($ret) {
		$page->redirect($ent->permalink());
	}
}

# Get the entry AFTER posting the comment so that the comment count is right.
$page->title = $ent->subject . " - " . $blg->name;
$content =  $ent->get( $blg->canModifyEntry() );

# Array for adding style sheets.  We build this as we go so that we don't 
# end up sending more stylesheets than we need to.
$page->addStylesheet("blogentry.css");

# Add TrackBacks for current entry.
$tmp_content = $ent->getTrackbacks();
$content .= $tmp_content;
if ($tmp_content) $page->addStylesheet("trackback.css");
# Now add the comments.
$tmp_content = $ent->getComments();
$content .= $tmp_content;
if ($tmp_content) $page->addStylesheet("comment.css");

# Add comment form if applicable.
if ($ent->allow_comment) { 
	$page->addStylesheet("form.css");
	$comm_tpl = NewTemplate(COMMENT_FORM_TEMPLATE);
	$comm_tpl->set("FORM_TARGET", $ent->uri("basepage"));
	$content .= $comm_tpl->process();
}

$page->addScript("entry.js");
$page->display($content, &$blog);
?>
