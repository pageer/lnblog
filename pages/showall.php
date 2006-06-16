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

# File: showall.php
# Displays a list of all entries in a blog, in reverse chronological order.
#
# This is included by the all.php wrapper script in the top-level entries 
# directory for blogs.

session_start();
require_once("lib/creators.php");

global $PAGe;

$blog = NewBlog();
$PAGE->setDisplayObject($blog);

$title = spf_("All entries for %s.", $blog->name);
$blog->getRecent(-1);

$tpl = NewTemplate(LIST_TEMPLATE);
$tpl->set("LIST_TITLE", spf_("Archive of %s", $title));

$LINK_LIST = array();

foreach ($blog->entrylist as $ent) { 
	$LINK_LIST[] = array("link"=>$ent->permalink(), "title"=>$ent->subject);
}

$tpl->set("LINK_LIST", $LINK_LIST);
$body = $tpl->process();

$PAGE->title = $blog->name." - ".$title;
$PAGE->display($body, &$blog);

?>
