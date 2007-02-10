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

# File: showcomments.php
# Displays all the comments on an entry or article.
# This also displays the comment addition form, if comments are enabled.
#
# This is included in the index.php wrapper script in the comments directory
# of entries and articles.

session_start();
require_once("config.php");
require_once("lib/creators.php");
require_once("pages/pagelib.php");

global $PAGE;

$ent = NewEntry();
$blg = NewBlog();
$usr = NewUser();
$PAGE->setDisplayObject($ent);
			
#$PAGE->title = htmlspecialchars($ent->subject . " - " . $blg->name);
$PAGE->title = htmlspecialchars($ent->subject) . " - " . $blg->name;

# This code will detect if a comment has been submitted and, if so,
# will add it.  We do this before printing the comments so that a 
# new comment will be displayed on the page.
# Here we include and call handle_comment() to output a comment form, add a 
# comment if one has been posted, and set "remember me" cookies.
$comm_output = '';
if ($ent->allow_comment) {
	require_once('pagelib.php');
	$comm_output = handle_comment($ent, true);
}

$content = '';

# Allow a query string to get just the comment form, not the actual comments.
if (! GET('post')) {
	$content = show_comments($ent, $usr);
	# Extra styles to add.  Build the list as we go to keep from including more
	# style sheets than we need to.
	$PAGE->addStylesheet("reply.css");
} elseif (! $ent->allow_comment) {
	$content = '<p>'._('Comments are closed on this entry.').'</p>';
}
$content .= $comm_output;

$PAGE->addScript("entry.js");
$PAGE->display($content, &$blg);
?>
