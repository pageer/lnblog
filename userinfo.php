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
# This will display the username, real name, e-mail, and URL for a user.
# Currently, this is not included in the default interface.

session_start();

require_once("blogconfig.php");
require_once("lib/creators.php");
require_once("lib/utils.php");

$uid = GET("user_id");
$uid = $uid ? $uid : POST("user_id");
$uid = preg_replace("/\W/", "", $uid);

$usr = NewUser($uid);
$page = NewPage(&$usr);
$tpl = NewTemplate(USER_INFO);
$usr->exportVars($tpl);

$ret = $tpl->process();
$user_file = USER_DATA_PATH.$uid."profile.htm";
if (file_exists($user_file)) {
	$ret .= implode("\n", file($user_file));
}

$page->title = _("User Information");
$page->display($ret);
?>
