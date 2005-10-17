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
require_once("lib/creators.php");
require_once("lib/utils.php");

$page = NewPage();

if ( ! file_exists(INSTALL_ROOT.PATH_DELIM.USER_DATA.PATH_DELIM.FS_PLUGIN_CONFIG) ) {
	$page->redirect("fs_setup.php");
	exit;
}

$update =  "update";
$upgrade = "upgrade";
$fixperm = "fixperm";
$page->title = PACKAGE_NAME." Administration";

$tpl = NewTemplate(BLOG_ADMIN_TEMPLATE);
$tpl->set("SHOW_NEW");
$tpl->set("UPDATE", $update);
$tpl->set("UPGRADE", $upgrade);
$tpl->set("PERMS", $fixperm);
$tpl->set("FORM_ACTION", current_file());

$usr = NewUser();
if (! is_file(USER_DATA_PATH.PATH_DELIM."passwd.php")) {
	$page->redirect("newlogin.php");
	exit;
} elseif (! $usr->checkLogin() || $usr->username() != ADMIN_USER) {
	$page->redirect("bloglogin.php");
	exit;
}

if (POST($upgrade)) {
	if (is_absolute(POST($upgrade))) $upgrade_path = trim(POST($upgrade));
	else $upgrade_path = calculate_document_root().PATH_DELIM.trim(POST($upgrade));
	$b = NewBlog($upgrade_path);
	$upgrade_status = $b->upgradeWrappers();
} elseif (POST($update)) {
	if (is_absolute(POST($update))) $update_path = trim(POST($update));
	else $update_path = calculate_document_root().PATH_DELIM.trim(POST($update));
	$page->redirect(htmlentities("updateblog.php?blogpath=".$update_path));
} elseif (POST($fixperm)) {
	if (is_absolute(POST($fixperm))) $fixperm_path = trim(POST($fixperm));
	else $fixperm_path = calculate_document_root().PATH_DELIM.trim(POST($fixperm));
	$b = NewBlog(POST($fixperm_path));
	#echo "<p>Fix perms</p>";
	$upgrade_status = $b->fixDirectoryPermissions();
}

$body = $tpl->process();
$page->display($body);
?>
