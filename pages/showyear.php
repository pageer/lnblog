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

require_once("config.php");
require_once("blog.php");
require_once("template.php");
$blog = new Blog();

$year_dir = basename(getcwd());
$title = $blog->name." - ".$year_dir;
$month_list = scan_directory(getcwd(), true);
sort($month_list);

$tpl = new PHPTemplate(ARCHIVE_TEMPLATE);
$tpl->set("ARCHIVE_TITLE", $year_dir);

$LINK_LIST = array();

foreach ($month_list as $month) { 
	$ts = mktime(0, 0, 0, $month, 1, $year_dir);
	$month_name = strftime("%B", $ts);
	$LINK_LIST[] = array("URL"=>$month, "DESC"=>$month_name);
}

$tpl->set("LINK_LIST", $LINK_LIST);
$body = $tpl->process();

$page_tpl = new PHPTemplate(BASIC_LAYOUT_TEMPLATE);
$blog->exportVars($page_tpl);
$page_tpl->set("PAGE_TITLE", $title);
$page_tpl->set("PAGE_CONTENT", $body);
echo $page_tpl->process();

?>
