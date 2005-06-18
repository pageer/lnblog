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
require_once("config.php");
require_once("blog.php");
require_once("blogentry.php");
require_once("template.php");

$blog = new Blog();

$title = "all entries for ".$blog->name;
$blog->getRecent(-1);

$tpl = new PHPTemplate(ARCHIVE_TEMPLATE);
$tpl->set("ARCHIVE_TITLE", $title);

$LINK_LIST = array();

foreach ($blog->entrylist as $ent) { 
	$LINK_LIST[] = array("URL"=>$ent->permalink(), "DESC"=>$ent->subject);
}

$tpl->set("LINK_LIST", $LINK_LIST);
$body = $tpl->process();
$title = $blog->name." - ".$title;

$page_tpl = new PHPTemplate(BASIC_LAYOUT_TEMPLATE);
$blog->exportVars($page_tpl);
$page_tpl->set("PAGE_TITLE", $title);
$page_tpl->set("PAGE_CONTENT", $body);
#$page_tpl->set("STYLE_SHEETS", array("blogentry.css") );
echo $page_tpl->process();

?>
