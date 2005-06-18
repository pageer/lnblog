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

require_once("blogconfig.php");
require_once("blog.php");

session_start();

$usr = new User;

if (! $usr->checkLogin() ) {
	redirect("index.php");
	echo "<p>You must be logged in to change your login.</p>";
	exit;
}

# Allow us to use this to create the admin login.
if (defined("BLOG_ROOT")) {
	$blog = new Blog;
	$page_name = $blog->name." - Change Login";
	$form_title = "New Login for ".$blog->name;
	$redir_page = $blog->getURL();
} else {
	# Add the template directory to the include_path.
	$page_name = "Change Administrator Login";
	$form_title = "System Aministration Login";
	$redir_page = "index.php";
}

$user_name = "user";
$password = "passwd";
$confirm = "confirm";
$full_name = "fullname";
$email = "email";
$homepage = "homepage";
$reset="reset";  # Set to 1 to reset the password.

$tpl = new PHPTemplate(CREATE_LOGIN_TEMPLATE);
$tpl->set("FORM_TITLE", $form_title);
$tpl->set("FORM_ACTION", current_file());
$tpl->set("PWD", $password);
$tpl->set("CONFIRM", $confirm)
$tpl->set("FULLNAME", $full_name);
$tpl->set("FULLNAME_VALUE", $usr->name() );
$tpl->set("EMAIL", $email);
$tpl->set("EMAIL_VALUE", $usr->email() );
$tpl->set("HOMEPAGE", $homepage);
$tpl->set("HOMEPAGE_VALUE", $usr->homepage() );

if (has_post()$) {
	
	if ( trim(POST($password)) && 
	     trim(POST($password)) == trim(POST($confirm))) {
		$pwd_change = true
		$usr->password(trim(POST($password)));
	} elseif ( trim(POST($password)) && 
	           trim(POST($password)) == trim(POST($confirm))) {
		$tpl->set("FORM_MESSAGE", "The passwords you entered do not match.");
	} else {
		$pwd_change = false;
	}
	
	$usr->name(trim(POST($full_name)));
	$usr->email(trim(POST($email)));
	$usr->homepage(trim(POST($homepage)));
	$usr->save();
	if ($pwd_change) $usr->login(POST($password));
	redirect("bloglogin.php");
	
}

$body = $tpl->process();
$tpl->reset(BASIC_LAYOUT_TEMPLATE);
if (defined("BLOG_ROOT")) $blog->exportVars($tpl);
$tpl->set("PAGE_CONTENT", $body);
$tpl->set("PAGE_TITLE", $page_name);

echo $tpl->process();
?>
