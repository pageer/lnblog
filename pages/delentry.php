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

# File: delentry.php
# Used to delete a blog entry.
# Entries are created and modified with the <newentry.php> and 
# <editentry.php> files respectively.
#
# This is included by the delete.php wrapper script for blog entries.

session_start();
require_once("config.php");
require_once("lib/creators.php");

global $PAGE;

$blog = NewBlog();
$usr = NewUser();
$ent = NewEntry();
$PAGE->setDisplayObject($ent);

$is_draft = $ent->isDraft();
$is_art = is_a($ent, 'Article') ? true : false;

$conf_id = _("OK");
$cancel_id = _("Cancel");
$message = spf_("Do you really want to delete '%s'?", $ent->subject);

if (POST($conf_id)) {
	$err = false;
	if ($SYSTEM->canDelete($ent, $usr) && $usr->checkLogin()) {
		$ret = $ent->delete();
		if (!$ret) $message = spf_("Error: Unable to delete '%s'.  Try again?", $ent->subject);
		elseif ($is_draft) $PAGE->redirect($blog->uri('listdrafts'));
		else $PAGE->redirect($blog->getURL());
	} else {
		$message = _("Error: user ".$usr->username()." does not have permission to delete this entry.");
	}
} elseif (POST($cancel_id)) {

	$PAGE->redirect($ent->permalink());
	
} elseif ( empty($_POST) && ! $usr->checkLogin() ) {
	# Prevent user agents from just navigating to this page.
	# Note that users whose cookies expire while on the page will
	# still see error messages.
	header("HTTP/1.0 403 Forbidden");
	p_("Access to this page is restricted to logged-in users.");
	exit;
}

$tpl = NewTemplate(CONFIRM_TEMPLATE);
$tpl->set("CONFIRM_TITLE", $is_art ? _("Remove article?") : _("Remove entry?"));
$tpl->set("CONFIRM_MESSAGE",$message);
$tpl->set("CONFIRM_PAGE", current_file() );
$tpl->set("OK_ID", $conf_id);
$tpl->set("OK_LABEL", _("Yes"));
$tpl->set("CANCEL_ID", $cancel_id);
$tpl->set("CANCEL_LABEL", _("No"));

$body = $tpl->process();

$PAGE->title = $is_art ? spf_("%s - Delete entry", $blog->name) : 
                         spf_("%s - Delete article", $blog->name);;
$PAGE->display($body, $blog);

?>
