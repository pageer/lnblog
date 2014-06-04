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

# File: about.php
# The "about LnBlog" page.
#
# This just shows the basic "about" information, like current version and license information.
session_start();
require_once("config.php");
require_once Path::mk(INSTALL_ROOT, "lib", "creators.php");

$tpl = NewTemplate("about_tpl.php");

$tpl->set("NAME", PACKAGE_NAME);
$tpl->set("VERSION", PACKAGE_VERSION);
$tpl->set("URL", PACKAGE_URL);
$tpl->set("NAME", PACKAGE_NAME);
$tpl->set("DESCRIPTION", PACKAGE_DESCRIPTION);
$tpl->set("COPYRIGHT", PACKAGE_COPYRIGHT);

$blog = NewBlog();
Page::instance()->setDisplayObject($blog);

$content = $tpl->process();
Page::instance()->display($content, $blog);