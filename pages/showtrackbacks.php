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
require_once("blog.php");
require_once("blogentry.php");
require_once("blogcomment.php");
require_once("rss.php");
require_once("tb.php");

$SUBMIT_ID = "submit";

$entry_path = dirname(getcwd());
$ent = new BlogEntry($entry_path);
$blg = new Blog();

# If there is a POST url, then this is a trackback ping.  Receive it and 
# exit.  Otherwise, display the page.
if (POST("url")) {
	$ret = $ent->getPing();
} else {			

	$title = $ent->subject . " - " . $blg->name;

	$content = $ent->getTrackbacks(); 

	$tpl = new PHPTemplate(BASIC_LAYOUT_TEMPLATE);
	$blg->exportVars($tpl);
	$tpl->set("PAGE_TITLE", $title);
	$tpl->set("PAGE_CONTENT", $content);
	$tpl->set("STYLE_SHEETS", array("trackback.css") );

	echo $tpl->process();
}
?>
