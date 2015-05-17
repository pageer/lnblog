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

# File: showarticles.php
# Show a list of all the articles for a weblog.
#
# This is included in the index.php wrapper script in the top-level articles
# directory of blogs.


session_start();
$pages = new WebPages();
$pages->showarticles();
exit;






session_start();

require_once("config.php");
require_once("lib/creators.php");

$blog = NewBlog();
Page::instance()->setDisplayObject($blog);

$year_dir = basename(getcwd());
$title = $blog->name." - ".$year_dir;
$list = scan_directory(getcwd(), true);
sort($list);

$tpl = NewTemplate(LIST_TEMPLATE);
$tpl->set("LIST_TITLE", spf_("%s articles", $blog->name));

$LINK_LIST = $blog->getArticleList(false, false);

$tpl->set("LINK_LIST", $LINK_LIST);
$body = $tpl->process();

Page::instance()->title = $title;
Page::instance()->display($body, $blog);
