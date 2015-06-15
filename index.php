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

function show_confirm_page($title, $message, $page, $yes_id, $yes_label, $no_id, $no_label, $data_id, $data) {
	$tpl = NewTemplate('confirm_tpl.php');
	$tpl->set('CONFIRM_TITLE', $title);
	$tpl->set('CONFIRM_MESSAGE', $message);
	$tpl->set('CONFIRM_PAGE', $page);
	$tpl->set('OK_ID', $yes_id);
	$tpl->set('OK_LABEL', $yes_label);
	$tpl->set('CANCEL_ID', $no_label);
	$tpl->set('CANCEL_LABEL', $no_label);
	$tpl->set('PASS_DATA_ID', $data_id);
	$tpl->set('PASS_DATA', $data);
	$form = $tpl->process();
	Page::instance()->display($form);
	exit;
}

if (isset($_GET['r'])) {
	$router = new RequestRouter($_GET['r']);
	$router->route();
	exit;
}

if ( ! file_exists(USER_DATA_PATH.PATH_DELIM.FS_PLUGIN_CONFIG) ) {
	Page::instance()->redirect("fs_setup.php");
}

$update =  "update";
$upgrade = "upgrade";
Page::instance()->title = sprintf(_("%s Administration"), PACKAGE_NAME);
Page::instance()->addScript();

$tpl = NewTemplate('blog_admin_tpl.php');
$tpl->set("SHOW_NEW");
$tpl->set("FORM_ACTION", current_file());

# Check if there is at least one administrator.  
# If not, then we need to create one.

$usr = NewUser();

if (! System::instance()->hasAdministrator()) {
	Page::instance()->redirect("newlogin.php");
} elseif (! $usr->checkLogin() || ! $usr->isAdministrator()) {
	Page::instance()->redirect("bloglogin.php");
}

if ( POST('upgrade') && POST('upgrade_btn') ) {
	
	$b = NewBlog(POST('upgrade'));
	$file_list = $b->upgradeWrappers();
	if (empty($file_list)) {
		$status = spf_("Upgrade of %s completed successfully.", $b->blogid);
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
		$ret = System::instance()->registerBlog($blog->blogid);
		if ($ret) {
			$status = spf_("Blog %s successfully registered.", $blog->blogid);
		} else {
			$status = spf_("Registration error: exited with code %s", $ret);
		}
	}
	$tpl->set("REGISTER_STATUS", $status);

} elseif ( POST('delete') && POST('delete_btn') ) {
	
	if (POST('confirm_form') || GET('confirm')) {
		
		$blog = NewBlog(POST('delete'));
		if (! $blog->isBlog()) {
			$status = spf_("The path '%s' is not an LnBlog weblog.", POST('delete'));
		} else {
			$ret = System::instance()->unregisterBlog($blog->blogid);
			$ret = $ret && $blog->delete();
			if ($ret) {
				$status = spf_("Blog %s successfully deleted.", $blog->blogid);
			} else {
				$status = spf_("Delete error: exited with code %s", $ret);
			}
		}
		$tpl->set("DELETE_STATUS", $status);
		
	} else {
		show_confirm_page(_("Confirm blog deletion"), spf_("Really delete blog '%s'?", POST('delete')), current_file(),
						  'delete_btn', _('Yes'), 'cancel_btn', _('No'), 'delete', POST('delete'));
	}

} elseif ( POST('fixperm') && POST('fixperm_btn') ) {
	$p = NewPath();
	if ($p->isAbsolute(POST('fixperm'))) {
		$fixperm_path = trim(POST('fixperm'));
	} else {
		$fixperm_path = calculate_document_root().PATH_DELIM.trim(POST('fixperm'));
	}
	$b = NewBlog(POST($fixperm_path));
	$upgrade_status = $b->fixDirectoryPermissions();
	if ($upgrade_status) {
		$status = _("Permission update completed successfully.");
	} else {
		$status = spf_("Error: Update exited with status %s.", $upgrade_status);
	}
	$tpl->set("FIXPERM_STATUS", $status);

} elseif (POST("username") && POST("edituser")) {

	$usr = NewUser();
	if ($usr->exists(POST("username"))) {
		Page::instance()->redirect("pages/editlogin.php?user=".POST('username'));
	} else {
		$status = spf_("User %s does not exist.", POST('username'));
	}

}

$blogs = System::instance()->getBlogList();
$blog_names = array();
foreach ($blogs as $blg) {
	$blog_names[] = $blg->blogid;
}
$tpl->set("BLOG_ID_LIST", $blog_names);

$users = System::instance()->getUserList();
$user_ids = array();
foreach ($users as $u) {
	$user_ids[] = $u->username();
}
$tpl->set("USER_ID_LIST", $user_ids);

$body = $tpl->process();
Page::instance()->display($body);
?>
