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

# This page is used to both send and recieve TrackBack pings.
# Currently, it uses a very primitive form of sending TrackBacks - a form.
# You must enter the TrackBack URL to ping and then confirm the ping data.
# Yeah, it sucks, and I'll probably do away with it soon.

session_start();

require_once("lib/creators.php");

global $PAGE;
global $SYSTEM;

$blog = NewBlog();
$ent = NewBlogEntry();
$usr = NewUser();
$tb = NewTrackback();
$PAGE->setDisplayObject($ent);

if ( GET("send_ping") == "yes" ) {

	$tpl = NewTemplate("send_trackback_tpl.php");
	
	if ($SYSTEM->canModify($ent, $usr) && $usr->checkLogin()) {
		
		# Set default values for the trackback properties.
		$tb->title = $ent->subject;
		$tb->blog = $blog->name;
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
					$tpl->set("ERROR_MESSAGE", _("Trackback ping succeded."));
				} else {
					$tpl->set("ERROR_MESSAGE", 
					          spf_('Error %s: %s', $ret['error'], $ret['message']));
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

	$body = $tpl->process();
	$PAGE->title = _("Send Trackback Ping");
	$PAGE->addStyleSheet("form.css");
	$PAGE->display($body, &$body);
	
} else {
	if ($ent->allow_tb) {
		$ret = $tb->receive();
	} else {
		$output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
		          "<response>\n".
		          "<error>1</error>\n";
		          "<message>"._("This entry does not accept trackbacks.")."</message>\n";
		          "</response>\n";
		echo $output;
	}
}
?>
