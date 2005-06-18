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

$blog = new Blog;
$ret = $blog->getWeblog(); 

$tpl = new PHPTemplate();
$tpl->file = BASIC_LAYOUT_TEMPLATE;
$blog->exportVars($tpl);
if (defined("BLOG_ROOT")) {
	if (is_file(BLOG_ROOT.PATH_DELIM.BLOG_FEED_PATH.PATH_DELIM.BLOG_RSS2_NAME)) {
		$tpl->set("XML_FEED", BLOG_ROOT_URL.BLOG_FEED_PATH."/".BLOG_RSS2_NAME);
		$tpl->set("XML_FEED_TITLE", $blog->name." RSS 2.0 feed");
	}
	if (is_file(BLOG_ROOT.PATH_DELIM.BLOG_FEED_PATH.PATH_DELIM.BLOG_RSS1_NAME)) {
		$tpl->set("RDF_FEED", BLOG_ROOT_URL.BLOG_FEED_PATH."/".BLOG_RSS1_NAME);
		$tpl->set("RDF_FEED_TITLE", $blog->name." RSS 1.0 feed");
	}
}
$tpl->set("PAGE_TITLE", $blog->name);
$tpl->set("PAGE_CONTENT", $ret);
$tpl->set("STYLE_SHEETS", array("blogentry.css") );

echo $tpl->process();
?>
