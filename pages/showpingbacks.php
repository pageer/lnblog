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

global $PAGE;

$u = NewUser();
$ent = NewBlogEntry();
$blg = NewBlog();
$PAGE->setDisplayObject($ent);

if ( GET("delete") ) {
	$pb = NewPingback(sanitize(GET("delete")));
	if ($SYSTEM->canDelete($pb, $u) && $u->checkLogin() ) {
		$pb->delete();
	}
}
$PAGE->title = $ent->subject . " - " . $blg->name;
$PAGE->addStylesheet("reply.css");
$body = $ent->getPingbacks();
if (! $body) $body = '<p>'.
	spf_('There are no pingbacks for <a href="%s">\'%s\'</a>',
	     $ent->permalink(), $ent->subject).'</p>';
$PAGE->display($body, &$blog);
?>
