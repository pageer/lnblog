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
$page = NewPage(&$ent);

if (GET("send_ping") == "yes" || POST("send_ping") == "yes") {
	
	if (! $blog->canModifyEntry() ) redirect("index.php");

	$tburl = "trackback_url";

	$tpl = NewTemplate(TRACKBACK_PING_TEMPLATE);
	if (POST($tburl)) {
		$tpl->set("TARGET_PAGE", POST($tburl) );
		$tpl->set("TB_URL", $ent->permalink() );
		$tpl->set("TB_TITLE", $ent->subject);
		$tpl->set("TB_BLOG", $blog->name);
		$tpl->set("TB_EXCERPT", $ent->markup() );
	} else {
		$tpl->set("TARGET_PAGE", current_file() );
		$tpl->set("GET_TB_URL", $tburl);
	}
	
	$body = $tpl->process();
	$page->title = "Send Trackback Ping";
	$page->addStylesheet("form.css");
	$page->display($body, &$blog);
	
} else {
	$ret = $ent->getPing();
}
?>
