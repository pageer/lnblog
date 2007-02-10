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

$u = NewUser();
$ent = NewBlogEntry();
$blg = NewBlog();
$PAGE->setDisplayObject($ent);
$tb = NewTrackback();

# If there is a POST url, then this is a trackback ping.  Receive it and 
# exit.  Otherwise, display the page.
if (! $tb->incomingPing()) {

	if ( GET("delete") ) {
		$tb = NewTrackback(sanitize(GET("delete")));
		if ($SYSTEM->canDelete($tb, $u) && $u->checkLogin() ) {
			$tb->delete();
		}
	}
	$PAGE->title = $ent->subject . " - " . $blg->name;
	$PAGE->addStylesheet("reply.css");
	$PAGE->addScript("entry.js");
	$body = show_trackbacks($ent, $u);
	if (! $body) {
		$body = '<p>'.
		        spf_('There are no trackbacks for <a href="%s">\'%s\'</a>',
		        $ent->permalink(), $ent->subject).'</p>';
	}
	$PAGE->display($body, &$blog);
} else {
	if ($ent->allow_tb) {
		$output = $tb->receive();
	} else {
		$output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
		          "<response>\n".
		          "<error>1</error>\n";
		          "<message>"._("This entry does not accept trackbacks.")."</message>\n";
		          "</response>\n";
	}
	echo $output;
}
?>
