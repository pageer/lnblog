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

$tags = htmlspecialchars(GET("tag"));
$show_posts = GET("show") ? true : false;
$limit = GET("limit") ? GET("limit") : 0;

if (! $tags) {
	
	$links = array();
	foreach ($blog->tag_list as $tag) {
		$links[] = array('link'=>$blog->uri('tags', $tag), 'title'=>ucwords($tag));
	}
	$tpl = NewTemplate(LIST_TEMPLATE);
	$tpl->set("LIST_TITLE", _("Topics for this weblog"));
	$tpl->set("LINK_LIST", $links);
	$body = $tpl->process();
	
} else {

	$tag_list = explode(",", $tags);
	
	if (! is_array($tag_list)) $tag_list = array();
	foreach ($tag_list as $key=>$val) {
		$tag_list[$key] = trim($val);
	}
	
	$ret = $blog->getEntriesByTag($tag_list, $limit, true);
	if ($show_posts) {
		$body = $blog->getWeblog();
		$PAGE->addStylesheet("entry.css");
	} else {
		$links = array();
		foreach ($ret as $ent) {
			$links[] = array("link"=>$ent->permalink(), "title"=>$ent->subject);
		}
		$tpl = NewTemplate(LIST_TEMPLATE);
		$tpl->set("LIST_TITLE", _("Entries filed under: ").implode(", ", $tag_list));
		$tpl->set("LIST_FOOTER", '<a href="?show=all&amp;tag='.$tags.'">'.
									_("Display all entries at once").'</a>');	
		$tpl->set("LINK_LIST", $links);
		$body = $tpl->process();
	}
	$PAGE->title = $blog->name.' - '._("Topic Search");
}

$PAGE->display($body);
?>
