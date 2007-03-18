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
require_once("pagelib.php");

function get_display_markup(&$item, $count) {
	$ret = '<a href="'.$item->permalink().'">'.$item->title()."</a>";
	$ret .= reply_boxes($count, $item);
	return $ret;
}

function has_posted_responses() {
	if (POST('responselist')) {
		return true;
	} elseif (POST('responseid0')) {
		$count = 0;
		while (POST("responseid$count")) $count++;
		for ($i = 0; $i <= $count; $i++) {
			if (POST("response$i")) 	return true;
		}
		return false;
	} else {
		return false;
	}
}

function get_posted_responses(&$response_array, &$denied_array) {
	$index = 1;
	$u = NewUser();
	
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

function get_archive_objects(&$blog, $type) {
	$ret = array();
	if (isset($_GET['year']) && isset($_GET['month'])) {
		$ents = $blog->getMonth(sanitize(GET('year'), '/\D/'), 
		                        sanitize(GET('month'), '/\D/'));
	} elseif (isset($_GET['year'])) {
		$ents = $blog->getYear(sanitize(GET('year'), '/\D/'));
	} else {
		return array();
	}
	
	$ret = array();
	foreach ($ents as $e) {
		$ret = array_merge($ret, get_reply_objects($e, $type));
	}
	return $ret;
}

function get_reply_objects(&$main_obj, $type) {
	switch ($type) {
		case 'comment':
			if (is_a($main_obj, 'Blog')) return $main_obj->getComments();
			else return $main_obj->getCommentArray();
		case 'trackback':
			if (is_a($main_obj, 'Blog')) return $main_obj->getTrackbacks();
			else return $main_obj->getTrackbackArray();
		case 'pingback':
			if (is_a($main_obj, 'Blog')) return $main_obj->getPingbacks();
			else return $main_obj->getPingbackArray();
		default:
			if (is_a($main_obj, 'Blog')) return $main_obj->getReplies();
			else return $main_obj->getAllReplies();
	}
}

function get_title($main_obj, $type) {
	
	if (is_a($main_obj, 'Blog')) $title = $main_obj->name;
	else $title = $main_obj->subject;
	
	$title = '<a href="'.
	         $main_obj->uri('permalink').
	         '">'.$main_obj->title().'</a>';
	
	if (GET('year')) $year = sanitize(GET('year'), '/\D/');
	else $year = false;
	
	if (GET('year') && GET('month')) {
		$year = sanitize(GET('year'), '/\D/');
		$month = sanitize(GET('month'), '/\D/');
		$date = fmtdate("%B %Y", strtotime("$year-$month-01"));
	} elseif (GET('year')) {
		$date = sanitize(GET('year'), '/\D/');
	} else {
		$date = false;
	}
	
	switch ($type) {
		case 'comment':
			$ret = $date ? spf_("Comments on entries for %s", $date):
			               spf_("All comments on '%s'", $title);
			break;
		case 'trackback':
			$ret = $date ? spf_("TrackBacks on entries for %s", $date):
			               spf_("All TrackBacks for '%s'", $title);
			break;
		case 'pingback':
			$ret = $date ? spf_("Pingbacks on entries for %s", $date):
			               spf_("All Pingbacks for '%s'", $title);
			break;
		default:
			$ret = $date ? spf_("Replies on entries for %s", $date):
			               spf_("All replies for '%s'", $title);
	}
	return $ret;
}

function show_reply_list(&$main_obj) {
	$tpl = NewTemplate(LIST_TEMPLATE);
	
	if (GET('year') || GET('month')) {
		$repl_array = get_archive_objects($main_obj, GET('type'));
	} else {
		$repl_array = get_reply_objects($main_obj, GET('type'));
	}
	
	$tpl->set('FORM_ACTION', make_uri(false, false, false));
	
	$tpl->set('LIST_TITLE', get_title($main_obj, GET('type')));
	$tpl->set('LIST_HEADER', 
			spf_("View reply type:").
			' <a href="'.make_uri(false, array('type'=>'all'), false).'">'.
			_("All Replies").'</a> | '.
			'<a href="'.make_uri(false, array('type'=>'comment'), false).'">'.
			_("Comments").'</a> | '.
			'<a href="'.make_uri(false, array('type'=>'trackback'), false).'">'.
			_("TrackBacks").'</a> | '.
			'<a href="'.make_uri(false, array('type'=>'pingback'), false).'">'.
			_("Pingbacks").'</a>');
	$tpl->set('FORM_FOOTER', '<input type="submit" value="'._('Delete').'" />');
	$ITEM_LIST = array();
	
	$idx = 0;
	foreach ($repl_array as $ent) {
		$ITEM_LIST[] = get_display_markup($ent, $idx++);
	}
	
	$tpl->set("ITEM_LIST", $ITEM_LIST);
	$tpl->set("ORDERED");
	return $tpl->process();
}

function handle_deletes() {

	$response_array = array();
	$denied_array = array();
	get_posted_responses($response_array, $denied_array);

	$tpl = NewTemplate('confirm_tpl.php');
	$tpl->set("CONFIRM_PAGE", make_uri(false, false, false) );
	$tpl->set("OK_ID", 'conf');
	$tpl->set("OK_LABEL", _("Yes"));
	$tpl->set("CANCEL_ID", _("Cancel"));
	$tpl->set("CANCEL_LABEL", _("Cancel"));
	$tpl->set("PASS_DATA_ID", "responselist");
	
	$anchors = '';
	
	if ( count($response_array) <= 0 ) {
		
		# No permission to delete anything, so bail out.
		
		$title = _("Permission denied");
		$message = _("You do not have permission to delete any of the selected responses.");
		
	} elseif ( count($response_array) > 0 && count($denied_array) > 0 ) {
	
		# We have some responses that can't be deleted, so confirm with the user.
	
		$title = _("Delete responses");
		$good_list = '';
		$bad_list = '';
		
		foreach ($response_array as $resp) {
			$anchors .= ($anchors == '' ? '' : ',').$resp->getAnchor();
			$good_list .= get_list_text($resp);
		}
		
		foreach ($denied_array as $obj) {
			$bad_list .= get_list_text($obj);
		}
	
		$message = _("Delete the following responses?").
				'<ul>'.$good_list.'</ul>'.
				_('The following responses will <strong>not</strong> be deleted because you do not have sufficient permissions.').
				'<ul>'.$bad_list.'</ul>';
	
	} elseif (POST('conf') || GET("conf") == "yes") {
		
		# We already have confirmation, either from the form or the query string, 
		# and a list of only valid responses, so now we can actually delete them.
		
		$ret = do_delete($response_array);
		
		if ( count($ret) > 0) {
			
			$list = '';
			
			foreach ($ret as $obj) {
				$anchors .= ($anchors == '' ? '' : ',').$resp->getAnchor();
				$list .= get_link_text($obj);
			}
			$title = _("Error deleting responses");
			$message = _("Unable to delete the following responses.  Do you want to try again?");
			$message .= $list;
		} else {
			# Success!  Clear the PASS_DATA_ID to prevent erroneous errors
			# when the page reloads.
			$tpl->set("PASS_DATA_ID", "");
			return true;
		}
	
	} else {
	
		# Last is the case where we have all good responses, but no confirmation.
		
		$list = '';
		
		foreach ($response_array as $resp) {
			$anchors .= ($anchors == '' ? '' : ',').$resp->getAnchor();
			$list .= get_list_text($resp);
		}
		
		$title = _("Delete responses");
		$message = _("Do you want to delete the following responses?");
		$message .= $list;
		
	}
	
	$tpl->set("CONFIRM_TITLE", $title);
	$tpl->set("CONFIRM_MESSAGE",$message);
	$tpl->set("PASS_DATA", $anchors);

	return $tpl->process();

}

global $PAGE;

$blog = NewBlog();
$ent = NewEntry();

if ( $ent && $ent->isEntry() ) {
	$main_obj = $ent;
} else {
	$main_obj = $blog;
}

$PAGE->setDisplayObject($main_obj);

if (has_posted_responses()) {
	$body = handle_deletes();
	if ($body === true) {
		#$PAGE->redirect(make_uri(false, false, false, '&'));
		# It seems that the POST data is passed on when you redirect to the same
		# page.  You learn something new every day.
		$body = show_reply_list($main_obj);
	}
} else {
	$body = show_reply_list($main_obj);
}

$PAGE->title = $blog->title()." - "._('Manage replies');
$PAGE->display($body, &$blog);
?>