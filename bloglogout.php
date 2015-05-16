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

# File: bloglogout.php
# Logs the user out and redirects them to the front page of the current blog.
# In the stardard setup, this is included by the logout.php wrapper script
# in each blog.

session_start();
require_once("blogconfig.php");
require_once("lib/creators.php");

$blog = NewBlog();
Page::instance()->setDisplayObject($blog);

$cancel_id = "cancel";
$ok_id = "ok";

if ($blog->isBlog()) $redir_url = $blog->getURL();
else                 $redir_url = "index.php";

$tpl = NewTemplate(CONFIRM_TEMPLATE);
$tpl->set("CONFIRM_TITLE", _("Logout"));
$tpl->set("CONFIRM_MESSAGE", _("Do you really want to log out?"));
$tpl->set("CONFIRM_PAGE", current_file());
$tpl->set("OK_ID", $ok_id);
$tpl->set("OK_LABEL", _("Yes"));
$tpl->set("CANCEL_ID", $cancel_id);
$tpl->set("CANCEL_LABEL", _("No"));

if (POST($ok_id)) {
	$usr = NewUser();
	$usr->logout();
	Page::instance()->redirect($redir_url);
} else if (POST($cancel_id)) {
	Page::instance()->redirect($redir_url);
}

$body = $tpl->process();
if ($blog->isBlog()) Page::instance()->title = sprintf(_("%s - Logout"), $blog->name);
else                 Page::instance()->title = _("Administration - Logout");
Page::instance()->display($body, $blog);

?>
