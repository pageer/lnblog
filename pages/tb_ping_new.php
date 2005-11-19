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

$blog = NewBlog();
$ent = NewBlogEntry();
$page = NewPage(&$ent)
$tburl = "trackback_url";

if (GET("send_ping") == "yes" || POST("send_ping") == "yes" ) {
	
	if (! $blog->canModifyEntry() ) redirect("index.php");

	$tpl = NewTemplate(TRACKBACK_PING_TEMPLATE);

	$tpl->set("TB_URL_ID", $tburl );
	$tpl->set("TB_URL", POST($tburl) );
	$tpl->set("TB_EXCERPT_ID", "excerpt");
	$tpl->set("TB_EXCERPT", $this->markup() );
	
	# If the form has been posted, send the trackback.
	if (POST($tburl)) {
		$ret = $ent->sendPing( POST($tburl), POST("excerpt") );
		$tpl->set("ERROR_MESSAGE", $ret);
	} else echo "<p>"._("Not posted")."</p>";

	$body = $tpl->process();
	$page->title = _("Send Trackback Ping");
	$page->display($body, &$body);
	
} else {
	$ret = $ent->getPing();
}
?>
