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
require_once("config.php"); 
require_once("database.php");
require_once("user.php");
require_once("blogentry.php");

$p = new Page;
$t = new TagGenerator;
$frm = new BasicForm("postform", $p->currentPage() );
$usr = new User;
$post = new BlogEntry;
$db = new Database;

$ret = false;

$db->connect();
if ( ! $usr->loggedIn($db) ) {
	$p->redirect(SITE_PRIVATE."login.php");
	$p->body .= $t->h("You must log in to add or modify a post.");
	echo $p->get();
	exit;
}

# Check for a post ID first and retrive the data if found.
$frm->fields["postid"] = new FormHidden("postid", "", "/^\d*$/");

if ($frm->fields["postid"]->value) {
	$post->getByID($frm->fields["postid"]->value, $db);
	$add_update = "Update";
} else $add_update = "Add";	
	
$p->title = $add_update." weblog entry - ".CFG_SITE_NAME; 
$p->body .= $t->h1($add_update." Weblog Entry");

$frm->fields["blogid"] = new FormHidden("blogid", $post->blogid, "/^\d+$/");
$frm->fields["subject"] = new FormBox("subject", "Subject", $post->subject, "/.+/");
$frm->fields["postdata"] = new FormEdit("postdata", "", $post->data, "/.+/");
$frm->fields["postdata"]->no_input_span = true;
$frm->fields["comments"] = new FormCheck("comments", "Allow comments", $post->allow_comments);

$form_is_submitted = $frm->submitted();
$form_is_valid = $frm->validate();

if ( $form_is_submitted && $form_is_valid ) {

	$post->postid = $frm->fields["postid"]->value;
	$post->blogid = $frm->fields["blogid"]->value;
	$post->subject = $frm->fields["subject"]->value;
	$post->data = $frm->fields["postdata"]->value;
	$post->allow_comments = $frm->fields["comments"]->value;

	if ( $frm->fields["postid"]->value ) $ret = $post->update($db);
	else $ret = $post->insert($db);

	if ($ret) {
		$p->body .= $t->h2("Entry updated successfully.");
		$p->refresh(SITE_ROOT."index.php?blog=".$post->blogid, 2);
	} else $p->body .= $t->h2("Unable to update entry at this time.");
	
} else if ( $form_is_submitted && ! $form_is_valid ) {
	$p->body .= $t->h2("Form contains invalid data.");
}

if (!$ret) $p->body .= $frm->get();
echo $p->get();

?>
