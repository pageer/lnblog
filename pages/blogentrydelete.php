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
require_once("html.php");
require_once("blogpage.php");
require_once("database.php");
require_once("user.php");
require_once("entry.php");
require_once("blogentry.php");

$p = new Page;
$t = new Tag;
$frm = new Form("delform", $p->currentPage());
$usr = new User;
$db = new Database;
$ent = new BlogEntry;

$get_id = $_GET["postid"];
$post_id = $_POST["postid"];
$currid = 
$button_yes = $_POST["yesbtn"];
$button_no = $_POST["nobtn"];
$confirm = $button_yes == "Yes" && $button_no == "";

$tmp =  $t->span( $t->input("yesbtn", "submit", "Yes"), "", "rowleft" ); 
$tmp .= $t->span( $t->input("nobtn", "submit", "No"), "", "rowright" );
$frm->markup .= $frm->row($tmp);
$frm->markup .= $t->input("postid", "hidden", $get_id);

$p->title = "Delete a weblog entry - ".CFG_SITE_NAME; 
$p->body = $t->div( $t->p("Delete post?") . $frm->get(), "formbox" );

$db->connect();

if ( ! $usr->loggedIn($db) ) {
	
	$p->body .= $t->h("You must log in to delete a post.");
	$p->redirect(SITE_PRIVATE."login.php");

} elseif ($post_id > 0 && $confirm) {
	
	$ret = $ent->getFromDB($post_id, $db);
	$p->redirect("index.php");
	
	if ($ret) {
		$ret = $ent->delete($db);
		if ($ret) {
			$p->body = $t->h("Post successfully deleted.");
			
		} else {
			$p->body = $t->h("Could not delete post.");
		}
	} else {
		$p->body = $t->h("Could not find post.");
	}
} elseif ($post_id > 0 && ! $confirm) {
	$p->redirect(SITE_ROOT);
	$p->body = $t->h("Post not deleted.");
}

$db->disconnect();
echo $p->get();
