<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2006 Peter A. Geer <pageer@skepticats.com>

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

# File: delcomment.php
# A unified page for deleting responses to an entry.  This handles both 
# individual and bulk deletes for comments, TrackBacks, and Pingbacks.
#
# This is included by the delete.php wrapper script in the comments 
# subdirectory of entries and articles.

session_start();
require_once("config.php");
require_once("lib/creators.php");
require_once("pagelib.php");

global $PAGE;

$blog = NewBlog();
$entry = NewEntry();
$u = NewUser();

if (! $u->checkLogin()) {
	$PAGE->redirect($entry->permalink());
	exit;
}

# which determines if the resposne is to be deleted.

$response_array = array();
$denied_array = array();
$index = 1;

# Get the list of items to be deleted.
# There are two possible cases.  First is the case where we've already confirmed
# via this page.  The second is where we're either about to confirm or have
# confirmed via a query string parameter.

if (POST('responselist')) {

	$anchors = explode(',', $_POST['responselist']);
	foreach ($anchors as $a) {
		$obj = get_response_object($a, $u);
		if ($obj) $response_array[] = $obj;
		else $denied_array[] = $a;
	}

} else {

	# For multiple deletes, there are two lists of fields.  The responseid# is the
	# anchor for that response, wile the response# is the corresponding checkbox 

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

$tpl = NewTemplate('confirm_tpl.php');
$tpl->set("CONFIRM_PAGE", current_file() );
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
		$PAGE->redirect($entry->permalink());
		exit;
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

$body = $tpl->process();
$PAGE->title = sprintf("%s - %s", $blog->name, $title);

$PAGE->display($body, $blog);

?>
