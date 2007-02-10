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

# File: manage_replies.php
# Displays a list of all entries in a blog, in reverse chronological order.
#
# This is included by the all.php wrapper script in the top-level entries 
# directory for blogs.

session_start();
require_once("config.php");
require_once("pagelib.php");
require_once("lib/creators.php");

function get_display_markup(&$item) {
	if (isset($item->title))  $desc = $item->title;
	else $desc = $item->subject;
	return '<a href="'.$item->permalink().'">'.$desc."</a>";
}

# Convert anchor names to objects and check the delete permissions on them.
# Returns false if the conversion or security check fails.
function get_response_object($anchor, &$usr) {
	global $SYSTEM;

	if ( preg_match('/^comment/', $anchor) ) {
		$ret = NewBlogComment($anchor);
		if (! $ret->isComment()) $ret = false;
	} elseif ( preg_match('/^trackback/', $anchor) ) {
		$ret = NewTrackback($anchor);
		if (! $ret->isTrackback()) $ret = false;
	} elseif ( preg_match('/^pingback/', $anchor) ) {
		$ret = NewPingback($anchor);
		if (! $ret->isPingback()) $ret = false;
	} else {
		$ret = false;
	}

	# If ret is a valid object, but usr doesn't have delete permission, then
	# return false.
	if ( $ret && ! $SYSTEM->canDelete($ret, $usr) ) $ret = false;

	return $ret;
}

function get_posted_reponses(&$response_array, &$denied_array) {
	
	if (POST('responselist')) {
	
		$anchors = explode(',', $_POST['responselist']);
		foreach ($anchors as $a) {
			$obj = get_response_object($a, $u);
			if ($obj) $response_array[] = $obj;
			else $denied_array[] = $a;
		}
	
	} else {
	
		# For multiple deletes, there are two lists of fields.  The responseid# 
		# is the anchor for that response, wile the response# is the 
		# corresponding checkbox 
	
		$index = 0;
		while ( isset($_POST['responseid'.$index]) ) {
			if ( POST('response'.$index) ) {
				$obj = get_response_object($_POST['responseid'.$index], $u);
				if ($obj) $response_array[] = $obj;
				else $denied_array[] = $_POST['responseid'.$index];
			}
			$index++;
		}
	
		# Here we extract any response that may have been passed in the query string.
		$getvars = array('comment', 'delete', 'response');
		foreach ($getvars as $var) {
			if (GET($var)) {
				$obj = get_response_object(GET($var), $u);
				if ($obj) $response_array[] = $obj;
				else $denied_array[] = GET($var);
			}
		}
	}
	
}

function get_reply_list(&$blog, &$ent) {
	if ( $ent && $ent->isEntry() ) {
		$use_ent = true;
		$obj =& $ent;
	} else {
		$use_ent = false;
		$obj =& $blog;
	}
	
	if (GET("type") == 'comment') {
		$method = $use_ent ? 'getCommentArray' : 'getEntryComments';
	} elseif (GET("type") == 'trackback') {
		$method = $use_ent ? 'getCommentArray' : 'getEntryComments';
	
	} elseif (GET("type") == 'pingback') {
		$method = $use_ent ? 'getCommentArray' : 'getEntryComments';
		$cmt_array = $blog->getEntryComments();
	
	} else {
		$method = $use_ent ? 'getReplyArray' : 'getEntryComments';
		$cmt_array = $blog->getEntryReplies();
		
	}
}

function get_reply_objects(&$blog, &$ent) {
	$type = GET('type');
	switch ($type) {
		case 'comment':
			if ($ent->isEntry()) {
				return $ent->getCommentArray();
			} else {
				return $blog->getComments();
			}
		case 'trackback':
			if ($ent->isEntry()) {
				return $ent->getTrackbackArray();
			} else {
				return $blog->getTrackbacks();
			}
		case 'pingback':
			if ($ent->isEntry()) {
				return $ent->getPingbackArray();
			} else {
				return $blog->getPingbacks();
			}
		default:
			return $blog->getReplies();
	}
}

global $PAGE;

$blog = NewBlog();
$ent = NewEntry();
$main_obj = $ent->isEntry() ? $ent : $blog;
$PAGE->setDisplayObject($main_obj);

$tpl = NewTemplate(LIST_TEMPLATE);

$repl_array = get_reply_objects($blog, GET('type'));
$title = spf_("All replies for %s.", $blog->name);

$LINK_LIST = array();

foreach ($repl_array as $ent) { 
	$LINK_LIST[] = array("link"=>$ent->permalink(), "title"=>"- ".$ent->subject);
}

$tpl->set("LINK_LIST", $LINK_LIST);
$tpl->set("ORDERED");
$body = $tpl->process();

$PAGE->title = $blog->name." - ".$title;
$PAGE->display($body, &$blog);

?>
