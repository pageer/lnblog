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

# File: bloglogin.php
# Allows users to log in, then redirects them back to the page they came 
# from.  In the "standard" setup, this page is included by the login.php
# wrapper script in each blog.

require_once("blogconfig.php");
require_once("lib/creators.php");

session_start();

global $PAGE;
$PAGE->setDisplayObject($blog);
$blog = NewBlog();

if ($blog->isBlog()) {
	$page_name = spf_("%s - Login", $blog->name);
	$form_name = spf_("%s Login", $blog->name);
	$redir_url = $blog->getURL();
	$admin_login = false;
} else {
#	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR."templates");
	$page_name = _("System Administration");
	$form_name = _("System Administration Login");
	$redir_url = "index.php";
	$admin_login = true;
}

$user_name = "user";
$password = "passwd";

$tpl = NewTemplate(LOGIN_TEMPLATE);
$tpl->set("FORM_TITLE", $form_name);
$tpl->set("FORM_ACTION", current_file());
$tpl->set("UNAME", $user_name);
$tpl->set("PWD", $password);
$tpl->set("REF", SERVER("HTTP_REFERER") );

if ( POST($user_name) && POST($password) ) {
	$usr = NewUser(trim(POST($user_name)));
	$ret = $usr->login(POST($password));
	if (POST("referer")) {
		if ( basename(POST("referer")) != current_file() && 
		     basename(POST("referer")) != "newlogin.php") {
		     #strpos(POST("referer"), localpath_to_uri(INSTALL_ROOT)) !== false 
			$tpl->set("REF", POST("referer") );
			$redir_url = POST("referer");
		}
	}
	# Throw up an error if a regular user tries to log in as administrator.
	if ( $admin_login && ! $usr->isAdministrator() ) {
		$tpl->set("FORM_MESSAGE", _("Only the administrator account can log into the administrative pages."));
	} else {
		if ($ret) $PAGE->redirect($redir_url);
		else $tpl->set("FORM_MESSAGE", _("Error logging in.  Please check your username and password."));
	}
}

$body = $tpl->process();
$PAGE->title = $page_name;
$PAGE->addStylesheet("form.css");
$PAGE->display($body, $blog);

?>
