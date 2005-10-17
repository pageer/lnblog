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

$ent = NewBlogEntry();
$blg = NewBlog();
$page = NewPage(&$ent);

# If there is a POST url, then this is a trackback ping.  Receive it and 
# exit.  Otherwise, display the page.
if (POST("url")) {
	$ret = $ent->getPing();
} else {			
	$page->title = $ent->subject . " - " . $blg->name;
	$page->addStylesheet("trackback.css");
	$page->display($ent->getTrackbacks(), &$blog);
}
?>
