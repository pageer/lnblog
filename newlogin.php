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

# File: newlogin.php
# Used to create a new user account.  
#
# When used to create the administrator account, the username box is locked.
# To change the administrator username, set the <ADMIN_USER> configuration
# constant in the userdata/userconfig.php file.

require_once("blogconfig.php");
require_once("lib/creators.php");

session_start();

function populate_fields(&$tpl) {
	global $user_name, $password, $confirm, $full_name, $email, $homepage;
	$tpl->set("UNAME_VALUE", trim(POST($user_name)));
	$tpl->set("PWD_VALUE", trim(POST($password)));
	$tpl->set("CONFIRM_VALUE", trim(POST($confirm)));
	$tpl->set("FULLNAME_VALUE", trim(POST($full_name)));
	$tpl->set("EMAIL_VALUE", trim(POST($email)));
	$tpl->set("HOMEPAGE_VALUE", trim(POST($homepage)));
}

global $PAGE;
$redir_page = "index.php";
$tpl = NewTemplate(CREATE_LOGIN_TEMPLATE);

# Check if there is at least one administrator.  
# If not, then we need to create one.
if ($SYSTEM->hasAdministrator()) {
	$page_name = _("Create New Login");
	$form_title = _("Create New Login");
	$first_user = false;
} else {
	$page_name = _("Create Administrator Login");
	$form_title = _("Create Aministration Login");
	$tpl->set("UNAME_VALUE", ADMIN_USER);
	#$tpl->set("DISABLE_UNAME", true);
	$first_user = true;
}

# If there is an administrator, then we want to restrict creating new accounts.
$u = NewUser();
if ($SYSTEM->hasAdministrator() && 
    (! $u->checkLogin() || ! $u->isAdministrator()) ) {
	$PAGE->redirect("index.php");
	exit;
}

$user_name = "user";
$password = "passwd";
$confirm = "confirm";
$full_name = "fullname";
$email = "email";
$homepage = "homepage";
$reset="reset";  # Set to 1 to reset the password.

$tpl->set("FORM_TITLE", $form_title);
$tpl->set("FORM_ACTION", current_file());
$tpl->set("UNAME", $user_name);
$tpl->set("PWD", $password);
$tpl->set("CONFIRM", $confirm);
$tpl->set("FULLNAME", $full_name);
$tpl->set("EMAIL", $email);
$tpl->set("HOMEPAGE", $homepage);

$cust_path = mkpath(USER_DATA_PATH,CUSTOM_PROFILE);
$cust_ini = NewINIParser($cust_path);
$section = $cust_ini->getSection(CUSTOM_PROFILE_SECTION);
$tpl->set("CUSTOM_FIELDS", $section);

$post_complete = POST($user_name) && POST($password) && POST($confirm);
$partial_post = POST($user_name) || POST($password) || POST($confirm);

if ($post_complete) {
	if ( POST($confirm) != POST($password) ) {
		$tpl->set("FORM_MESSAGE", 
			"<span style=\"color: red\">".
			_("The passwords you entered do not match.").
			"</span>");
		populate_fields($tpl);
	} else {
		$usr = NewUser();
		$usr->username(trim(POST($user_name)));
		$usr->password(trim(POST($password)));
		$usr->name(trim(POST($full_name)));
		$usr->email(trim(POST($email)));
		$usr->homepage(trim(POST($homepage)));
		foreach ($section as $key=>$val) {
			$usr->custom[$key] = POST($key);
		}
		
		$ret = $usr->save();
		
		if ($ret) {
			if ($first_user) $ret = $usr->addToGroup('administrators');
			if (!$ret) {
				$tpl->set("FORM_MESSAGE", 
				          _("Error: Failed to make this user an administrator."));
				populate_fields($tpl);
			}
		} else {
			$tpl->set("FORM_MESSAGE", 
			          _("Error: Failed to save user information."));
			populate_fields($tpl);
		}
		
		if ($ret) {
			$PAGE->redirect("index.php");
			exit;
		}
	}
} elseif ($partial_post) {
	# Let's do them in reverse, so that the most logical message appears.
	if (! POST($confirm)) $tpl->set("FORM_MESSAGE", 
			'<span style="color: red">'.
			_("You must confirm your password.").
			'</span>');
	if (! POST($password)) $tpl->set("FORM_MESSAGE", 
			'<span style="color: red">'.
			_("You must enter a password.").
			'</span>');
	if (! POST($user_name)) $tpl->set("FORM_MESSAGE", 
			'<span style="color: red">'.
			_("You must enter a username.").
			'</span>');
	populate_fields($tpl);
}

$body = $tpl->process();
$PAGE->title = $page_name;
$PAGE->addStylesheet("form.css");
$PAGE->display($body);
?>
