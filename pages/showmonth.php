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

# Display a all blog entries for a given month.

session_start();
require_once("config.php");
require_once("lib/creators.php");

$blog = NewBlog();
$page = NewPage(&$blog);

$list = $blog->getMonth(); 

if (isset($list[0])) {
	$ts = $list[0]->timestamp;
} elseif ( sanitize(GET("year"), "/\D/") && 
           sanitize(GET("month"), "/\D/") ) {
	$ts = strtotime(GET("year")."-".GET("month")."-01");
} else {
	$ts = time();
}
if (USE_STRFTIME) {
	$title = fmtdate("%B %Y", $ts);
	$year = fmtdate("%Y", $ts);
} else {
	$title = fmtdate("F Y", $ts);
	$year = fmtdate("Y", $ts);
}

# Optionally show all the entries as a weblog.
if (strtolower(GET("show")) == "all") {
	$body = $blog->getWeblog();
	$page->addStylesheet("blogentry.css");
} else {

	$tpl = NewTemplate(LIST_TEMPLATE);
	$tpl->set("LIST_TITLE", $title);
	$tpl->set("LIST_FOOTER", 
	          '<a href="?show=all">'._("Show all entries at once").'</a>'.
	          '<br /><a href="'.$blog->getURL().BLOG_ENTRY_PATH."/$year/"
	          .'">'.spf_("Back to archive of %s", $year).'</a>');

	$LINK_LIST = array();
	foreach ($list as $ent) { 
		$LINK_LIST[] = array("link"=>$ent->permalink(), "title"=>$ent->subject);
	}
	
	if (empty($LINK_LIST)) {
		$tpl->set("LIST_HEADER", _("There are no entries for this month."));
	}
	$tpl->set("LINK_LIST", $LINK_LIST);
	$body = $tpl->process();
}

$page->title = $blog->name." - ".$title;
$page->display($body, &$blog);

?>
