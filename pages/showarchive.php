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

# File: showarchive.php
# Shows a list of the available years of archives for blog entries.
#
# This is included by the index.php wrapper script in the top-level entries
# directory of blogs.

session_start();

require_once("config.php");
require_once("lib/creators.php");

global $PAGE;

$blog = NewBlog();
$PAGE->setDisplayObject($blog);

$year_dir = basename(getcwd());
$title = $blog->name." - ".$year_dir;
$year_list = scan_directory(getcwd(), true);
sort($year_list);

$tpl = NewTemplate(LIST_TEMPLATE);
$tpl->set("LIST_TITLE", spf_("Archive of %s", $blog->name));

$LINK_LIST = $blog->getyearList();
foreach ($LINK_LIST as $key=>$item) $LINK_LIST[$key]["title"] = $item["year"];

$tpl->set("LINK_LIST", $LINK_LIST);
$tpl->set("LIST_FOOTER", 
          '<a href="'.$blog->getURL().BLOG_ENTRY_PATH.'/all.php">'.
          _("List all entries").'</a>');
$body = $tpl->process();

$PAGE->title = $title;
$PAGE->display($body, &$blog);
?>
