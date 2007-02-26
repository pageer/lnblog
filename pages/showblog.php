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

# File: showblog.php
# Shows the standard front page of weblog with the most recent entries.
#
# This is included by the index.php wrapper script of blogs.

# Function: show_blog_page
# Shows the main blog page.  This is typically the front page of the blog.
function show_blog_page(&$blog) {
	global $PAGE;
	$ret = $blog->getWeblog(); 
	$PAGE->title = $blog->title();
	$PAGE->addStylesheet("entry.css");
	return $ret;
}

if ( isset($_GET['action']) ) {
	if ( strtolower($_GET['action']) == 'newentry' ) {
		include('pages/entryedit.php');
		exit;
	} elseif ( strtolower($_GET['action']) == 'delentry' ) {
		include('pages/delentry.php');
		exit;
	} elseif ( strtolower($_GET['action']) == 'edit' ) {
		include('pages/updateblog.php');
		exit;
	} elseif ( strtolower($_GET['action']) == 'login' ) {
		include('bloglogin.php');
		exit;
	} elseif ( strtolower($_GET['action']) == 'logout' ) {
		include('bloglogout.php');
		exit;
	} elseif ( strtolower($_GET['action']) == 'upload' ) {
		include('pages/fileupload.php');
		exit;
	} elseif ( strtolower($_GET['action']) == 'sitemap' ) {
		include('sitemap.php');
		exit;
	} elseif ( strtolower($_GET['action']) == 'useredit' ) {
		include('pages/editlogin.php');
		exit;
	} elseif ( strtolower($_GET['action']) == 'plugins' ) {
		include('plugin_setup.php');
		exit;
	} elseif ( strtolower($_GET['action']) == 'tags' ) {
		include('pages/tagsearch.php');
		exit;
	} elseif ( strtolower($_GET['action']) == 'pluginload' ) {
		include('plugin_loading.php');
		exit;
	} elseif ( strtolower($_GET['action']) == 'profile' ) {
		include('userinfo.php');
		exit;
	} elseif ( strtolower($_GET['action']) == 'managereply' ) {
		include('pages/manage_replies.php');
		exit;
	}
}

session_start();
require_once("config.php");
require_once("lib/creators.php");

global $PAGE;

$blog = NewBlog();
$PAGE->setDisplayObject($blog);

$content = show_blog_page($blog);
$PAGE->display($content, &$blog);
?>
