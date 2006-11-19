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

# File: editlogin.php
# Used to modify user information, including e-mail address and password.
# You can also add custom fields to the profile that will appear on this 
# page by creating a profile.ini file.  See the <userprofiles.txt> 
# documentation for details.
#
# To create a new user, refer to the <newlogin.php> file.
#
# This is included by the useredit.php wrapper script for blogs.

require_once("config.php");
require_once("lib/creators.php");

session_start();

global $PAGE;

$usr = NewUser();
$PAGE->setDisplayObject($usr);
$blog = NewBlog();

if (! $usr->checkLogin() ) {
	if ($blog->isBlog()) $PAGE->redirect($blog->uri('blog'));
	else $PAGE->redirect(INSTALL_ROOT_URL);
	exit;
}

# Allow us to use this to create the admin login.
if ($blog->isBlog()) {
	$page_name = _("Change User Information");
	$form_title = spf_("New Login for %s", $blog->name);
	$redir_page = $blog->uri('blog');
} else {
	# Add the template directory to the include_path.
	$page_name = _("Change Administrator Login");
	$form_title = _("System Aministration Login");
	$redir_page = INSTALL_ROOT_URL;
}

$form_title = spf_("Modify User - %s", $usr->username());
$user_name = "user";
$reset="reset";  # Set to 1 to reset the password.

$tpl = NewTemplate("login_create_tpl.php");
$tpl->set("FORM_TITLE", $form_title);
$tpl->set("FORM_ACTION", current_file());
$tpl->set("FULLNAME_VALUE", htmlentities($usr->name()) );
$tpl->set("EMAIL_VALUE", htmlentities($usr->email()) );
$tpl->set("HOMEPAGE_VALUE", htmlentities($usr->homepage()) );

$blog_qs = ($blog->isBlog() ? "blog=".$blog->blogid."&amp;" : "");
$tpl->set("UPLOAD_LINK", 
          INSTALL_ROOT_URL."pages/fileupload.php?".
          $blog_qs."profile=".$usr->username() );
$tpl->set("PROFILE_EDIT_LINK", $blog->uri("editfile", "file=profile.htm", 
                                          'profile='.$usr->username() ) );
$tpl->set("PROFILE_EDIT_DESC", _("Edit extra profile data") );
$tpl->set("UPLOAD_DESC", _("Upload file to profile") );

# Populate the form with custom profile fields.
$priv_path = mkpath(USER_DATA_PATH,$usr->username(),CUSTOM_PROFILE);
$cust_path = mkpath(USER_DATA_PATH,CUSTOM_PROFILE);
$cust_ini = NewINIParser($priv_path);
$cust_ini->merge(NewINIParser($cust_path));

$section = $cust_ini->getSection(CUSTOM_PROFILE_SECTION);
$tpl->set("CUSTOM_FIELDS", $section);
$tpl->set("CUSTOM_VALUES", $usr->custom);

if (has_post()) {
	
	if ( trim(POST('passwd')) && 
	     trim(POST('passwd')) == trim(POST('confirm'))) {
		$pwd_change = true;
		$usr->password(trim(POST('passwd')));
	} elseif ( trim(POST('passwd')) && 
	           trim(POST('passwd')) == trim(POST('confirm'))) {
		$tpl->set("FORM_MESSAGE", _("The passwords you entered do not match."));
	} else {
		$pwd_change = false;
	}
	
	$usr->name(trim(POST('fullname')));
	$usr->email(trim(POST('email')));
	$usr->homepage(trim(POST('homepage')));
	
	foreach ($section as $key=>$val) {
		$usr->custom[$key] = trim(POST($key));
	}
	
	$usr->save();
	if ($pwd_change) $usr->login(POST('passwd'));
	$PAGE->redirect($redir_page);
	
}

$body = $tpl->process();
if (! defined("BLOG_ROOT")) $blog = false;;
$PAGE->addStylesheet("form.css");
$PAGE->title = $page_name;
$PAGE->display($body, &$blog);
?>
