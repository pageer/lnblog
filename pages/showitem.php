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

# Function: show_comment_page
# Show the page of comments on the entry.
function show_comment_page(&$blg, &$ent, &$usr) {
	global $PAGE;
	
	$PAGE->title = $ent->title() . " - " . $blg->title();
	
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

	$PAGE->addScript(lang_js());
	$PAGE->addScript("entry.js");
	
	return $content;

}

# Function: show_pingback_page
# Show the page of Pingbacks for the entry.
function show_pingback_page(&$blg, &$ent, &$usr) {
	global $PAGE;

	$PAGE->title = $ent->title() . " - " . $blg->title();
	$PAGE->addScript(lang_js());
	$PAGE->addStylesheet("reply.css");
	$PAGE->addScript("entry.js");
	$body = show_pingbacks($ent, $usr);
	if (! $body) $body = '<p>'.
		spf_('There are no pingbacks for &s', 
		     sprintf('<a href="%s">\'%s\'</a>',
			         $ent->permalink(), $ent->subject)).'</p>';
	return $body;
}

# Function: show_trackback_page
# Shows the page of TrackBacks for the entry.
function show_trackback_page(&$blg, &$ent, &$usr) {
	global $PAGE;
	
	$PAGE->title = $ent->title() . " - " . $blg->title();
	$PAGE->addScript(lang_js());
	$PAGE->addStylesheet("reply.css");
	$PAGE->addScript("entry.js");
	$body = show_trackbacks($ent, $usr);
	if (! $body) {
		$body = '<p>'.spf_('There are no trackbacks for %s',
		                   sprintf('<a href="%s">\'%s\'</a>', 
				                   $ent->permalink(), $ent->subject)).'</p>';
	}
	return $body;
}

# Function: show_trackback_ping_page
# Show the page from which users can send a TrackBack ping.
function show_trackback_ping_page(&$blog, &$ent, &$usr) {
	global $SYSTEM;
	global $PAGE;
	
	$tpl = NewTemplate("send_trackback_tpl.php");

	if ($SYSTEM->canModify($ent, $usr) && $usr->checkLogin()) {
		$tb = NewTrackback();
		
		# Set default values for the trackback properties.
		$tb->title = $ent->title();
		$tb->blog = $blog->title();
		$tb->data = $ent->getAbstract();
		$tb->url = $ent->permalink();

		# If the form has been posted, send the trackback.
		if (has_post()) {

			$tb->url = trim(POST('url'));
			$tb->blog = POST('blog_name');
			$tb->data = POST('excerpt');
			$tb->title = POST('title');

			if ( ! trim(POST('target_url')) || ! POST('url') ) {
				$tpl->set("ERROR_MESSAGE", _("You must supply an entry URL and a target URL."));
			} else {
				$ret = $tb->send( trim(POST('target_url')) );
				if ($ret['error'] == '0') {
					$refresh_time = 5;
					$tpl->set("ERROR_MESSAGE", 
					          spf_("Trackback ping succeded.  You will be returned to the entry in %d seconds.", $refresh_time));
					$PAGE->refresh($ent->permalink(), $refresh_time);
				} else {
					ob_start();
					print_r($ret);
					$all_resp = ob_get_contents();
					ob_end_clean();
					$tpl->set("ERROR_MESSAGE", 
					          spf_('Error %s: %s', $ret['error'], $ret['message']).
							  '<br /><textarea rows="20" cols="20">'.
							  $all_resp.'</textarea>');
				}
			}
		}

		$tpl->set("TB_URL", $tb->url );
		$tpl->set("TB_TITLE", $tb->title);
		$tpl->set("TB_EXCERPT", $tb->data );
		$tpl->set("TB_BLOG", $tb->blog);
		$tpl->set("TARGET_URL", trim(POST('target_url')));

	} else {
		$tpl->set("ERROR_MESSAGE", 
		          spf_("User %s cannot send trackback pings from this entry.",
		               $usr->username()));
	}

	
	$PAGE->title = _("Send Trackback Ping");
	$PAGE->addStyleSheet("form.css");

	return $tpl->process();	
}

# Function: show_entry_page
# Handles displaying the main permalink for a BlogEntry or Article.
function show_entry_page(&$blg, &$ent, &$usr) {
	global $SYSTEM;
	global $PAGE;
	
	# Here we include and call handle_comment() to output a comment form, add a 
	# comment if one has been posted, and set "remember me" cookies.
	$comm_output = '';
	if ($ent->allow_comment) {
		$comm_output = handle_comment($ent);
	}
	
	# Get the entry AFTER posting the comment so that the comment count is right.
	$PAGE->title = $ent->title() . " - " . $blg->title();
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
		$PAGE->addHeader("X-Pingback", INSTALL_ROOT_URL."xmlrpc.php");
		$PAGE->addLink(array('rel'=>'pingback',
							 'href'=>INSTALL_ROOT_URL."xmlrpc.php"));
	}
	$PAGE->addScript(lang_js());
	$PAGE->addScript("entry.js");
	$PAGE->addStylesheet("reply.css");
	$PAGE->addStylesheet("entry.css");
	
	return $content;
}

# Handle inclusion of other pages.  This is basically the "wrapper wrapper" 
# portion of the script.
if ( isset($_GET['action']) && strtolower($_GET['action']) == 'upload' ) {
	require_once("fileupload.php");
	exit;
} elseif ( isset($_GET['action']) && strtolower($_GET['action']) == 'edit' ) {
	require_once("entryedit.php"); 
	exit;
} elseif ( isset($_GET['action']) && strtolower($_GET['action']) == 'managereplies' ) {
	require_once("manage_replies.php"); 
	exit;
}

session_start();
require_once("config.php");
require_once("lib/creators.php");
require_once("pages/pagelib.php");

global $PAGE;

$ent = NewEntry();
$blg = NewBlog();
$usr = NewUser();
$PAGE->setDisplayObject($ent);

$page_type = strtolower(GET('show'));
if (! $page_type) {
	$page_type = basename(getcwd());
}

$tb = NewTrackback();

if ($tb->incomingPing() && strtolower(GET('action')) != 'ping') {
	
	if ($ent->allow_tb) {
		$content = $tb->receive();
	} else {
		$content = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
		           "<response>\n".
		           "<error>1</error>\n";
		           "<message>"._("This entry does not accept trackbacks.")."</message>\n";
		           "</response>\n";
	}

} elseif ( strtolower(GET("action")) == 'ping' ) {

	$content = show_trackback_ping_page(&$blg, &$ent, &$usr);

} elseif ( $page_type == 'pingback' || $page_type == ENTRY_PINGBACK_DIR) {

	$content = show_pingback_page(&$blg, &$ent, &$usr);

} elseif ( $page_type == 'trackback' || $page_type == ENTRY_TRACKBACK_DIR ) {

	$content = show_trackback_page(&$blg, &$ent, &$usr);

} elseif ( $page_type == 'comment' || $page_type == ENTRY_COMMENT_DIR ) {

	$content = show_comment_page(&$blg, &$ent, &$usr);

} else {

	$content = show_entry_page(&$blg, &$ent, &$usr);

}

$PAGE->display($content);
?>
