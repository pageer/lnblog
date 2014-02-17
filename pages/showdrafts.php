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

if (isset($_GET['action']) && $_GET['action'] == 'edit') {
	include('entryedit.php');
	exit;
}

session_start();

require_once("config.php");
require_once("lib/creators.php");

function draft_item_markup(&$ent) {
	global $blog;
	$del_uri = $ent->uri('delete');
	$edit_uri = $ent->uri('editDraft');
	$title = $ent->subject ? $ent->subject : $ent->date;
	$ret = '<a href="'.$edit_uri.'">'.$title.'</a> '.
	       '<span style="font-size: 80%; color: gray;">' . date("Y-m-d", $ent->post_ts) .
	       '</span> (<a href="'.$del_uri.'">'._("Delete").'</a>)';
	return $ret;
}

global $PAGE;

$list_months = false;

$blog = NewBlog();
$usr = User::get();
$PAGE->setDisplayObject($blog);

if (! $usr->checkLogin() || ! $SYSTEM->canModify($blog, $usr)) {
	Page::instance()->error(403);
}

$title = spf_("%s - Drafts", $blog->name);

$tpl = NewTemplate(LIST_TEMPLATE);
$tpl->set("LIST_TITLE", spf_("Drafts for %s", $blog->name));

$drafts = $blog->getDrafts();
$linklist = array();
foreach ($drafts as $d) {
	$linklist[] = draft_item_markup($d);
}

$tpl->set("ITEM_LIST", $linklist);
$body = $tpl->process();

$PAGE->title = $title;
$PAGE->display($body, $blog);
