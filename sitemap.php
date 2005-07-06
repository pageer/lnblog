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
session_start();

require_once("blogconfig.php");
require_once("blog.php");
require_once("utils.php");
require_once("template.php");
require_once("user.php");

$u = new User();

if (defined("BLOG_ROOT")) {
	$blog = new Blog();
	if (! $blog->canModifyBlog() ) {
		redirect("index.php");
		exit;
	}
} elseif (! $u->checkLogin() && ! $u->isAdministrator() ) {
	redirect("index.php");
	exit;
}

$tpl = new PHPTemplate(SITEMAP_TEMPLATE);
$tpl->set("FORM_ACTION", current_file() );

if (has_post()) {

	if (defined("BLOG_ROOT")) {
		$target_file = BLOG_ROOT.PATH_DELIM.SITEMAP_FILE;
	} else {
		$target_file = INSTALL_ROOT.PATH_DELIM.SITEMAP_FILE;
	}

	if (get_magic_quotes_gpc()) $data = stripslashes(POST("output"));
	else $data = POST("output");
	$ret = write_file($target_file, $data);

	if (! $ret) {
		$tpl->set("SITEMAP_ERROR", "Cannot create file");
		$tpl->set("ERROR_MESSAGE", "Unable to create file ".$target_file.".");
		$tpl->set("CURRENT_SITEMAP", $data);
	}
	
} else {

	if (defined("BLOG_ROOT") && is_file(BLOG_ROOT.PATH_DELIM.SITEMAP_FILE) ) {
		$target_file = BLOG_ROOT.PATH_DELIM.SITEMAP_FILE;
	} elseif (is_file(INSTALL_ROOT.PATH_DELIM.SITEMAP_FILE) ) {
		$target_file = INSTALL_ROOT.PATH_DELIM.SITEMAP_FILE;
	} else {
		$target_file = false;
	}

	if ($target_file) {
		$data = implode("\n", file($target_file) );
		$tpl->set("CURRENT_SITEMAP", $data);
	}
	
}

$page_tpl = new PHPTemplate(BASIC_LAYOUT_TEMPLATE);
if (defined("BLOG_ROOT")) $blog->exportVars($page_tpl);
$page_tpl->set("PAGE_TITLE", "Edit ".(defined("BLOG_ROOT")?"blog":"site")." menu bar");
$page_tpl->set("PAGE_CONTENT", $tpl->process() );
$page_tpl->set("STYLE_SHEETS", array("form.css") );
$page_tpl->set("SCRIPTS", array("sitemap.js") );
echo $page_tpl->process();

?>
