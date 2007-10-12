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

# File: userinfo.php
# Displays the public information for a user.
#
# This will display the username, real name, e-mail, URL, and other 
# information for a user.  This page is linked to the user's name in the # by-line of posts and comments.
#
# This displays built-in profile information, extra fields 
# added in a profile.ini file, and any narative set in the
# user's profile.htm file.

session_start();
require_once("blogconfig.php");
#require_once("lib/creators.php");
require_once("lib/utils.php");

global $PAGE;

$uid = GET("user");
$uid = $uid ? $uid : POST("user");
$uid = $uid ? $uid : basename(getcwd());
$uid = preg_replace("/\W/", "", $uid);

$usr = NewUser($uid);
$tpl = NewTemplate("user_info_tpl.php");
$PAGE->setDisplayObject($usr);
$usr->exportVars($tpl);

$priv_path = mkpath(USER_DATA_PATH,$usr->username(),"profile.ini");
$cust_path = mkpath(USER_DATA_PATH,"profile.ini");
$cust_ini = NewINIParser($priv_path);
$cust_ini->merge(NewINIParser($cust_path));

$tpl->set("CUSTOM_FIELDS", $cust_ini->getSection("profile fields"));
$tpl->set("CUSTOM_VALUES", $usr->custom);

$ret = $tpl->process();
$user_file = mkpath(USER_DATA_PATH,$uid,"profile.htm");

if (file_exists($user_file)) {
	$ret .= implode("\n", file($user_file));
}

$PAGE->title = _("User Information");
$PAGE->display($ret);
?>
