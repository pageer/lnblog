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

# File: showday.php
# Displays a page showing all blog entries posted on a given day.
# The day desired must be passed in the query string.
# 
# This is included in the day.php wrapper script in the month directories
# of each year of blog archives.

session_start();
require_once("config.php");
require_once("lib/creators.php");

global $PAGE;

$blog = NewBlog();
$PAGE->setDisplayObject($blog);

$curr_dir = getcwd();

if (GET('month')) $month = GET('month');
else              $month = basename($curr_dir);

if (GET('year')) $year = GET('year');
else             $year = basename(dirname($curr_dir));

$day = sprintf("%02d", GET("day") );

$ret = $blog->getDay($year, $month, $day);

if (count($ret) == 1) {
	$PAGE->redirect( $ret[0]->permalink() );
	exit;
} elseif (count($ret) == 0) {
	$body = spf_("No entry found for %d-%d-%d", $year, $month, $day);
} else {
	$body = $blog->getWeblog();
}

$PAGE->title = $blog->name." - ".spf_("Entries for %s", $day);
$PAGE->addStylesheet("blogentry.css");
$PAGE->display($body, &$blog);

?>
