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
require_once("lib/creators.php");

session_start();

$page = NewPage();
$redir_page = "index.php";
$tpl = NewTemplate(CREATE_LOGIN_TEMPLATE);

# Allow us to use this to create the admin login.
if (file_exists(USER_DATA_PATH.PATH_DELIM."passwd.php")) {
	$page_name = "Create New Login";
	$form_title = "Create New Login";
} else {
	$page_name = "Create Administrator Login";
	$form_title = "Create Aministration Login";
	$tpl->set("UNAME_VALUE", ADMIN_USER);
	$tpl->set("DISABLE_UNAME", true);
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

$post_complete = POST($user_name) && POST($password) && POST($confirm);
$partial_post = POST($user_name) || POST($password) || POST($confirm);

if ($post_complete) {
	if ( POST($confirm) != POST($password) ) {
		$tpl->set("FORM_MESSAGE", "The passwords you entered do not match.");
	} else {
		$usr = NewUser();
		$usr->username(trim(POST($user_name)));
		$usr->password(trim(POST($password)));
		$usr->name(trim(POST($full_name)));
		$usr->email(trim(POST($email)));
		$usr->homepage(trim(POST($homepage)));
		$usr->save();
		#$usr->login(POST($password));
		#redirect("bloglogin.php");
		$page->redirect("index.php");
		exit;
	}
} elseif ($partial_post) {
	# Let's do them in reverse, so that the most logical message appears.
	if (! POST($confirm)) $tpl->set("FORM_MESSAGE", "You must confirm your password.");
	if (! POST($password)) $tpl->set("FORM_MESSAGE", "You must enter a password.");
	if (! POST($user_name)) $tpl->set("FORM_MESSAGE", "You must enter a username.");
}

$body = $tpl->process();
$page->title = $page_name;
$page->addStylesheet("form.css");
$page->display($body);
?>
