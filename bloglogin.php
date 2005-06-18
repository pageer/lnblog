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

$blog = new Blog;

if (defined("BLOG_ROOT")) {
	$page_name = $blog->name;
	$redir_url = $blog->getURL();
} else {
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR."templates");
	$page_name = "System Administration";
	$redir_url = "index.php";
}

$user_name = "user";
$password = "passwd";

$tpl = new PHPTemplate(LOGIN_TEMPLATE);
$tpl->set("FORM_TITLE", $page_name." Login");
$tpl->set("FORM_ACTION", current_file());
$tpl->set("UNAME", $user_name);
$tpl->set("PWD", $password);

if ( POST($user_name) && POST($password) ) {
	$ret = do_login(POST($user_name), POST($password));
	if ($ret) redirect($redir_url);
	else $tpl->set("FORM_MESSAGE", "Error logging in.  Please check your username and password.");
}

$body = $tpl->process();
$tpl->reset(BASIC_LAYOUT_TEMPLATE);
$blog->exportVars($tpl);
$tpl->set("PAGE_CONTENT", $body);
$tpl->set("PAGE_TITLE", $page_name." - Login");
$tpl->set("STYLE_SHEETS", array("form.css") );

echo $tpl->process();
?>
