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

require_once("blogconfig.php");
require_once("utils.php");
require_once("blog.php");

if ( ! file_exists(INSTALL_ROOT.PATH_DELIM.FS_PLUGIN_CONFIG) ) {
	redirect("fs_setup.php");
	exit;
}

$update =  "update";
$upgrade = "upgrade";
$fixperm = "fixperm";
$page_title = PACKAGE_NAME." Administration";

$tpl = new PHPTemplate(BLOG_ADMIN_TEMPLATE);
$tpl->set("SHOW_NEW");
$tpl->set("UPDATE", $update);
$tpl->set("UPGRADE", $upgrade);
$tpl->set("PERMS", $fixperm);
$tpl->set("FORM_ACTION", current_file());

$logged_in = check_login();
if (! is_file("passwd.php")) redirect("newlogin.php");
elseif (! $logged_in) redirect("bloglogin.php");

if (POST($upgrade)) {
	$b = new Blog(POST($upgrade));
	$upgrade_status = $b->upgradeWrappers();
} elseif (POST($update)) {
	refresh(htmlentities("updateblog.php?blogpath=".POST($update)));
} elseif (POST($fixperm)) {
	$b = new Blog(POST($fixperm));
	#echo "<p>Fix perms</p>";
	$upgrade_status = $b->fixDirectoryPermissions();
}

$body = $tpl->process();
$tpl->reset(BASIC_LAYOUT_TEMPLATE);
$tpl->set("PAGE_CONTENT", $body);
$tpl->set("PAGE_TITLE", $page_title);
echo $tpl->process();
?>
