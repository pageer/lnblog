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
# To create a new user, refer to the <newlogin.php> file.
#
# This is included by the useredit.php wrapper script for blogs.

require_once("blogconfig.php");
require_once("lib/creators.php");

session_start();

$usr = NewUser();
$page = NewPage(&$usr);

if (! $usr->checkLogin() ) {
	$page->redirect("index.php");
	exit;
}

# Allow us to use this to create the admin login.
if (defined("BLOG_ROOT")) {
	$blog = NewBlog();	
	$page_name = _("Change User Information");
	$form_title = sprintf(_("New Login for %s"), $blog->name);
	$redir_page = $blog->getURL();
} else {
	# Add the template directory to the include_path.
	$page_name = _("Change Administrator Login");
	$form_title = _("System Aministration Login");
	$redir_page = "index.php";
}

$form_title = sprintf(_("Modify User - %s"), $usr->username());
$user_name = "user";
$password = "passwd";
$confirm = "confirm";
$full_name = "fullname";
$email = "email";
$homepage = "homepage";
$reset="reset";  # Set to 1 to reset the password.

$tpl = NewTemplate(CREATE_LOGIN_TEMPLATE);
$tpl->set("FORM_TITLE", $form_title);
$tpl->set("FORM_ACTION", current_file());
$tpl->set("PWD", $password);
$tpl->set("CONFIRM", $confirm);
$tpl->set("FULLNAME", $full_name);
$tpl->set("FULLNAME_VALUE", $usr->name() );
$tpl->set("EMAIL", $email);
$tpl->set("EMAIL_VALUE", $usr->email() );
$tpl->set("HOMEPAGE", $homepage);
$tpl->set("HOMEPAGE_VALUE", $usr->homepage() );

if (has_post()) {
	
	if ( trim(POST($password)) && 
	     trim(POST($password)) == trim(POST($confirm))) {
		$pwd_change = true;
		$usr->password(trim(POST($password)));
	} elseif ( trim(POST($password)) && 
	           trim(POST($password)) == trim(POST($confirm))) {
		$tpl->set("FORM_MESSAGE", _("The passwords you entered do not match."));
	} else {
		$pwd_change = false;
	}
	
	$usr->name(trim(POST($full_name)));
	$usr->email(trim(POST($email)));
	$usr->homepage(trim(POST($homepage)));
	$usr->save();
	if ($pwd_change) $usr->login(POST($password));
	$page->redirect("index.php");
	
}

$body = $tpl->process();
if (! defined("BLOG_ROOT")) $blog = false;;
$page->addStylesheet("form.css");
$page->title = $page_name;
$page->display($body, &$blog);
?>
