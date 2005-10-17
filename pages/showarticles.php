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

$blog = NewBlog();
$page = NewPage(&$blog);

$year_dir = basename(getcwd());
$title = $blog->name." - ".$year_dir;
$list = scan_directory(getcwd(), true);
sort($list);

$tpl = NewTemplate(ARCHIVE_TEMPLATE);
$tpl->set("ARCHIVE_TITLE", $blog->name." articles");

$LINK_LIST = $blog->getArticleList(false, false);

$tpl->set("LINK_LIST", $LINK_LIST);
$body = $tpl->process();

$page->title = $title;
$page->display($body, &$blog);

?>
