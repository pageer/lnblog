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

# File: index.php
# The page for the main administration menu.
#
# This page has a number of administration options, some of which are 
# vestigial.  The options are as follows.
#
# Modify site-wide menubar        - Modifies the "site map" in the menu bar.
# Add new user                    - Creates a new user.
# Add new blog                    - Creates a new weblog.
# Update blog data                - Upgrade format of blog storage files.
# Upgrade blog to current version - Recreate wrapper scripts for a blog.
# Fix directory permissions       - Make blog file permissions sane.
# Log out                         - Log out of the admin pages.
#
# Of the above options, fixing directory permissions and updating blog data
# are largely unused.  Directory permissions are only relevant when switching
# file writing methods, and even then this option isn't too useful.
#
# Updating blog data applies mostly to a previous change in the storage 
# format.  It is possible that this will be used again in the future, but 
# there are no plans for it.
#
# The upgrading to the current version, on the other hand, is more relevant.
# It is used to create a new set of wrapper scripts in the standard setup.
# "Wrappers scripts", as used by LnBlog, are simply very small PHP scripts
# (usually less than five lines) that do nothing but use the PHP include()
# statement to execute another script.  The purpose of these scripts is to
# make all functions relevant to a blog accessible under the root blog URL,
# thus giving a clean URL structure to the entire blog.  Since new features
# requiring new pages are added from time to time, this upgrade function
# will probably be used once every few upgrades, depending on the changes
# between releases.
#
# It is also important to note that the event system includes 
# OnUpgrade and UpgradeComplete events for the <Blog> object.  These events
# are raised when this upgrade feature is run, so that plugins may perform
# any needed updates at that time.

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
$page->title = sprintf(_("%s Administration"), PACKAGE_NAME);

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
	$page->redirect(current_file());
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
