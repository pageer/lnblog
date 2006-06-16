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

$blog = NewBlog();
$PAGE->setDisplayObject($blog);
$month_list = $blog->getMonthList();

foreach ($month_list as $key=>$val) { 
	$ts = mktime(0, 0, 0, $val["month"], 1, $val["year"]);
	if (! isset($year)) $year = $val["year"];
	if (USE_STRFTIME) {
		$month_name = fmtdate("%B", $ts);
	} else {
		$month_name = fmtdate("F", $ts);
	}
	$month_list[$key]["title"] = $month_name;
}

$tpl = NewTemplate(LIST_TEMPLATE);
$tpl->set("LIST_TITLE", spf_("Archive of %s", $year));
$tpl->set("LIST_FOOTER", 
          '<a href="'.$blog->getURL().BLOG_ENTRY_PATH.'/">'.
          spf_("Back to main archives", $year).'</a>');

$tpl->set("LINK_LIST", $month_list);
$body = $tpl->process();
$PAGE->display($body, &$blog);

?>
