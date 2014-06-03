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

function script_path($name) {
	if ( defined("BLOG_ROOT") && 
	     file_exists(BLOG_ROOT.'/scripts/'.$name) ) {
		
		return BLOG_ROOT.'/scripts/'.$name;
		
	# Second case: Try the userdata directory
	} elseif ( defined('THEME_NAME') && defined('USER_DATA_PATH') &&
	           file_exists(USER_DATA_PATH.'/themes/'.THEME_NAME.'/scripts/'.$name) ) {
		return USER_DATA_PATH."/themes/".THEME_NAME."/scripts/".$name;

	# Third case: check the current theme directory
	} elseif ( defined('INSTALL_ROOT') && defined('THEME_NAME') && 
	           file_exists(INSTALL_ROOT."/themes/".THEME_NAME.'/scripts/'.$name) ) {
		return INSTALL_ROOT."/themes/".THEME_NAME.'/scripts/'.$name;

	# Fourth case: try the default theme
	} elseif ( defined('INSTALL_ROOT') && 
	           file_exists(INSTALL_ROOT."/themes/default/scripts/$name") ) {
		return INSTALL_ROOT."/themes/default/scripts/$name";

	# Last case: nothing found, so return the original string.
	} else {
		return $name;
	}
}

$action_map = array(
	'about'      => 'pages/about.php',
	'newentry'   => 'pages/entryedit.php',
	'delentry'   => 'pages/delentry.php',
	'edit'       => 'pages/updateblog.php',
	'login'      => 'bloglogin.php',
	'logout'     => 'bloglogout.php',
	'upload'     => 'pages/fileupload.php',
	'sitemape'   => 'sitemap.php',
	'useredit'   => 'pages/editlogin.php',
	'plugins'    => 'plugin_setup.php',
	'tags'       => 'pages/tagsearch.php',
	'pluginload' => 'plugin_loading.php',
	'profile'    => 'userinfo.php',
	'managereply'=> 'pages/manage_replies.php',
	'editfile'   => 'pages/editfile.php'
);

if ( isset($_GET['action']) && isset($action_map[$_GET['action']]) ) {
	
	$action = str_replace('/', DIRECTORY_SEPARATOR, $action_map[$_GET['action']]);
	$filepath = Path::mk(INSTALL_ROOT, $action);
	include $filepath;
	exit;
	
} elseif ( isset($_GET['script']) ) {
	
	$file = script_path($_GET['script']);
	if (file_exists($file)) readfile($file);
	else echo "// Failed to find $file";
	exit;
	
} elseif ( isset($_GET['plugin']) ) {
	
	require_once("config.php");
	#ini_set("include_path", 
    #    ini_get('include_path').PATH_SEPARATOR.USER_DATA_PATH);
	define("PLUGIN_DO_OUTPUT", true);
	$paths = array(@BLOG_ROOT, USER_DATA_PATH, INSTALL_ROOT);
	foreach ($paths as $path) {
		$plugin = Path::mk($path, 'plugins', $_GET['plugin'].".php");
		if (file_exists($plugin)) {
			require $plugin;
			exit;
		}
	}
	exit;
}

session_start();
require_once "config.php";
require_once Path::mk(INSTALL_ROOT, "lib", "creators.php");

$blog = NewBlog();
$blog->autoPublishDrafts();
Page::instance()->setDisplayObject($blog);

$content = show_blog_page($blog);
Page::instance()->display($content, $blog);
