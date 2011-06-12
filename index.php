<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005-2011 Peter A. Geer <pageer@skepticats.com>

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

global $PAGE;

if ( ! file_exists(USER_DATA_PATH.PATH_DELIM.FS_PLUGIN_CONFIG) ) {
	$PAGE->redirect("fs_setup.php");
	exit;
}

$update =  "update";
$upgrade = "upgrade";
$PAGE->title = sprintf(_("%s Administration"), PACKAGE_NAME);

$tpl = NewTemplate('blog_admin_tpl.php');
$tpl->set("SHOW_NEW");
$tpl->set("FORM_ACTION", current_file());

# Check if there is at least one administrator.  
# If not, then we need to create one.

$usr = NewUser();
if (! $SYSTEM->hasAdministrator()) {
	$PAGE->redirect("newlogin.php");
	exit;
} elseif (! $usr->checkLogin() || ! $usr->isAdministrator()) {
	$PAGE->redirect("bloglogin.php");
	exit;
}

if ( POST('upgrade') && POST('upgrade_btn') ) {
	
	$b = NewBlog(POST('upgrade'));
	$file_list = $b->upgradeWrappers();
	if (empty($file_list)) {
		$status = spf_("Upgrade of %s completed successfully.",
		               $b->blogid);
	} elseif ($file_list === false) {
		$status = spf_("Error: %s does not seem to exist.", $b->blogid);
	} else {
		$status = spf_("Error: The following file could not be written - %s.", 
		               implode("<br />", $file_list));
	}
	$tpl->set("UPGRADE_STATUS", $status);

} elseif ( POST('register') && POST('register_btn') ) {
	$blog = NewBlog(POST('register'));
	if (! $blog->isBlog()) {
		$status = spf_("The path '%s' is not an LnBlog weblog.", POST('register'));
	} else {
		$ret = $SYSTEM->registerBlog($blog->blogid);
		if ($ret) $status = spf_("Blog %s successfully registered.", $blog->blogid);
		else $status = spf_("Registration error: exited with code %s", $ret);
	}
	$tpl->set("REGISTER_STATUS", $status);
	
} elseif ( POST('fixperm') && POST('fixperm_btn') ) {
	$p = NewPath();
	if ($p->isAbsolute(POST('fixperm'))) $fixperm_path = trim(POST('fixperm'));
	else $fixperm_path = calculate_document_root().PATH_DELIM.trim(POST('fixperm'));
	$b = NewBlog(POST($fixperm_path));
	$upgrade_status = $b->fixDirectoryPermissions();
	if ($upgrade_status) $status = _("Permission update completed successfully.");
	else $status = spf_("Error: Update exited with status %s.", $upgrade_status);
	$tpl->set("FIXPERM_STATUS", $status);

} elseif (POST("username") && POST("edituser")) {

	$usr = NewUser();
	if ($usr->exists(POST("username"))) {
		$PAGE->redirect("pages/editlogin.php?user=".POST('username'));
	} else {
		$status = spf_("User %s does not exist.", POST('username'));
	}

}

$blogs = $SYSTEM->getBlogList();
$blog_names = array();
foreach ($blogs as $blg) {
	$blog_names[] = $blg->blogid;
}
$tpl->set("BLOG_ID_LIST", $blog_names);

$users = $SYSTEM->getUserList();
$user_ids = array();
foreach ($users as $u) {
	$user_ids[] = $u->username();
}
$tpl->set("USER_ID_LIST", $user_ids);

$body = $tpl->process();
$PAGE->display($body);
?>
