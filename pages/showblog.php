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

# File: showblog.php
# Shows the standard front page of weblog with the most recent entries.
#
# This is included by the index.php wrapper script of blogs.

session_start();
require_once("config.php");
require_once("lib/creators.php");

$blog = NewBlog();
$page = NewPage($blog);
$ret = $blog->getWeblog(); 
$page->title = $blog->name;
$page->addStylesheet("blogentry.css");
$page->display($ret, &$blog);
?>
