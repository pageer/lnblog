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
$page = new WebPages();
$page->showarchive();
exit;


require_once("config.php");
require_once("lib/creators.php");

global $PAGE;
global $SYSTEM;

function show_base_archives(&$blog) {
	global $SYSTEM;
	
	$tpl = NewTemplate(LIST_TEMPLATE);
	
	if ( strtolower(GET('list')) == 'yes') {
		$list = $blog->getRecentMonthList(0);
		foreach ($list as $key=>$item) {
			$ts = strtotime($item['year'].'-'.$item['month'].'-01');
			$list[$key]["title"] = strftime("%B %Y", $ts);
		}
		$footer_text = '<a href="'.
		               make_uri(false, array("list"=>"no"), false).'">'.
		               _("Show year list").'</a>';
	} else {
		$list = $blog->getYearList();
		foreach ($list as $key=>$item) {
			$list[$key]["title"] = $item["year"];
		}
		$footer_text = '<a href="'.
		               make_uri(false, array("list"=>"yes"), false).'">'.
		               _("Show month list").'</a>';
	}
	
	if ($SYSTEM->canModify($blog))
		$footer_text .= ' | <a href="'.
		                $blog->uri('manage_all').'">'.
		                #make_uri(false, array('action'=>'manage_reply'), false).
		                _("Manage replies").'</a>';
	
	$tpl->set('LIST_TITLE', get_list_title($blog));
	if (empty($list)) {
		$tpl->set("LIST_HEADER", _("There are no entries for this blog."));
	} else {
		$tpl->set("LIST_FOOTER", $footer_text);
	}
	
	$tpl->set('LINK_LIST', $list);
	
	return $tpl->process();
}

function show_year_archives(&$blog, $year) {
	global $SYSTEM;
	$tpl = NewTemplate(LIST_TEMPLATE);
	
	if ( strtolower(GET('list')) == 'yes' ) {
		$ents = $blog->getYear($year);
		$links = array();
		foreach ($ents as $ent) {
			$lnk = array('link'=>$ent->permalink(), 'title'=>$ent->title());
			$links[] = $lnk;
		}
		$footer_text = '<a id="list_toggle" href="'.
					make_uri(false, array("list"=>"no"), false).'">'.
					_("Show month list").'</a>';
	} else {
		$links = $blog->getMonthList($year);
		foreach ($links as $key=>$val) { 
			$ts = mktime(0, 0, 0, $val["month"], 1, $val["year"]);
			$month_name = fmtdate("%B", $ts);
			$links[$key]["title"] = $month_name;
		}
		
		$footer_text = '<a href="'.make_uri(false, array("list"=>"yes"), false).'">'.
						_("Show entry list").'</a>';
	}
	
	if ($SYSTEM->canModify($blog))
		$footer_text .= ' | <a href="'.
		                $blog->uri('manage_year', $year).'">'.
		                #make_uri(false, array('action'=>'manage_reply'), false).
		                _("Manage replies").'</a>';
	$footer_text .= " | ".
	                '<a href="'.$blog->uri('archives').'/">'.
	                _("Back to main archives").'</a>';
	
	$tpl->set('LIST_TITLE', get_list_title($blog, $year));
	if (empty($links)) {
		$tpl->set("LIST_HEADER", _("There are no entries for this year."));
	} else {
		$tpl->set("LIST_FOOTER", $footer_text);
	}
	$tpl->set('LINK_LIST', $links);
	
	return $tpl->process();
}

function show_month_archives(&$blog, $year, $month) {
	global $SYSTEM;
	global $PAGE;
	$list = $blog->getMonth($year, $month);

	if ( strtolower(GET('show')) == 'all' ) {
		$PAGE->addStylesheet("entry.css");
		return $blog->getWeblog();
	} else {
		
		$tpl = NewTemplate(LIST_TEMPLATE);
		
		$links = array();
		foreach ($list as $ent) { 
			$links[] = array("link"=>$ent->permalink(), "title"=>$ent->subject);
		}

		$footer_text = '<a href="?show=all">'.
		               _("Show all entries at once").'</a>'.
		               ($SYSTEM->canModify($blog) ?
		                ' | <a href="'.$blog->uri('manage_month', $year, $month).'">'.
		                _("Manage replies").'</a>' : '').
		               ' | <a href="'.$blog->getURL().BLOG_ENTRY_PATH."/$year/".
		               '">'.spf_("Back to archive of %s", $year).'</a>';
	
		$tpl->set('LIST_TITLE', get_list_title($blog, $year, $month));
		if (empty($links)) {
			$tpl->set("LIST_HEADER", _("There are no entries for this month."));
		} else {
			$tpl->set('LIST_FOOTER', $footer_text);
		}
		
		$tpl->set('LINK_LIST', $links);
		
		return $tpl->process();
	}
}

function show_day_archives(&$blog, $year, $month, $day) {
	global $SYSTEM;
	global $PAGE;

	$ret = $blog->getDay($year, $month, $day);

	if (count($ret) == 1) {
		$body = array(true, $ret[0]->permalink());
	} elseif (count($ret) == 0) {
		$body = spf_("No entry found for %d-%d-%d", $year, $month, $day);
	} else {
		$PAGE->addStyleSheet("entry.css");
		$body = $blog->getWeblog();
	}
	return $body;
}

function get_list_title(&$blog, $year=false, $month=false) {
	if ($month) {
		$date = fmtdate("%B %Y", strtotime("$year-$month-01"));
	} elseif ($year) {
		$date = fmtdate("%Y", strtotime("$year-01-01"));
	} else {
		$date = "'".$blog->title()."'";
	}
	return spf_("Archives for %s", $date);
}

$blog = NewBlog();
$PAGE->setDisplayObject($blog);

$monthdir = basename(getcwd());
$yeardir = basename(dirname(getcwd()));
$month = false;

# First, check for the month and year in the query string.
if (sanitize(GET('month'), '/\D/') && 
    sanitize(GET('year'), '/\D/') ) {
	$month = sanitize(GET('month'), '/\D/');
	$year = sanitize(GET('year'), '/\D/');
	
# Failing that, try the directory names.
} elseif ( preg_match('/^\d\d$/', $monthdir) &&
           preg_match('/^\d\d\d\d$/',$yeardir) ) {
	$month = $monthdir;
	$year = $yeardir;
	
# If THAT fails, then there must not be a month, so try just the year.
} elseif ( sanitize(GET('year'), '/\D/') ) {
	$year = sanitize(GET('year'), '/\D/');
} elseif ( preg_match('/^\d\d\d\d$/',$monthdir) ) {
	$year = $monthdir;

# If we still don't have a year, show the base archives.
} else {
	$year = false;
}

$day = isset($_GET['day']) ? sprintf("%02d", GET("day") ) : false;

if ($year && $month && $day) {
	
	$body = show_day_archives($blog, $year, $month, $day);
	if (is_array($body)) {
		$PAGE->redirect( $body[1] );
		exit;
	}

} elseif ($year && $month) {
	
	$body = show_month_archives($blog, $year, $month);
	
} elseif ($year) {

	$body = show_year_archives($blog, $year);

} else {

	$body = show_base_archives($blog);
	
}

if (GET('ajax')) {
	echo $body;
} else {
	$PAGE->display($body, $blog);
}
