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
require_once("pages/pagelib.php");

global $PAGE;

$ent = NewEntry();
$blg = NewBlog();
$usr = NewUser();
$PAGE->setDisplayObject($ent);

# Here we include and call handle_comment() to output a comment form, add a 
# comment if one has been posted, and set "remember me" cookies.
$comm_output = '';
if ($ent->allow_comment) {
	require_once('pagelib.php');
	$comm_output = handle_comment($ent);
}

# Get the entry AFTER posting the comment so that the comment count is right.
$PAGE->title = $ent->subject . " - " . $blg->name;
$show_ctl = $SYSTEM->canModify($ent, $usr) && $usr->checkLogin();
$content =  $ent->get($show_ctl);

if ($SYSTEM->sys_ini->value("entryconfig", "GroupReplies", 0)) {
	$content .= show_all_replies($ent, $usr);
} else {
	$content .= show_trackbacks($ent, $usr);
	$content .= show_pingbacks($ent, $usr);
	$content .= show_comments($ent, $usr);
}

# Add comment form if applicable.
$content .= $comm_output;

if ($ent->enclosure) {
	$enc = $ent->getEnclosure();
	if ($enc) {
		$enc_arr = array("rel"=>_('enclosure'), 
		                 "href"=>$enc['url'], 
		                 "type"=>$enc['type']);
		$PAGE->addLink($enc_arr);
	}
}

if ($ent->allow_pingback) {
	$PAGE->addHeader("X-Pingback", INSTALL_ROOT_URL."pingback.php");
	$PAGE->addLink(array('rel'=>'pingback',
	                     'href'=>INSTALL_ROOT_URL."pingback.php"));
}
$PAGE->addScript("entry.js");
$PAGE->addStylesheet("reply.css");
$PAGE->addStylesheet("entry.css");
$PAGE->display($content, &$blog);
?>
