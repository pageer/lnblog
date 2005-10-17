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

# This file is used to display a blog entry with its comments.

session_start();
require_once("lib/creators.php");

$blog = NewBlog();
$page = NewPage(&$blog);

$title = "all entries for ".$blog->name;
$blog->getRecent(-1);

$tpl = NewTemplate(ARCHIVE_TEMPLATE);
$tpl->set("ARCHIVE_TITLE", $title);

$LINK_LIST = array();

foreach ($blog->entrylist as $ent) { 
	$LINK_LIST[] = array("link"=>$ent->permalink(), "title"=>$ent->subject);
}

$tpl->set("LINK_LIST", $LINK_LIST);
$body = $tpl->process();

$page->title = $blog->name." - ".$title;
$page->display($body, &$blog);

?>
