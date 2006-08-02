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

# File: editentry.php
# Used to edit existing blog entries and articles.
# To create a new blog entry, refer to the <newentry.php> file.
#
# When editing entries, it is possible to include a file upload in the POST.
# The number of uploads is determined by the AllowInitUpload variable 
# in the entryconfig section of your LnBlog/userdata/system.ini.  The default
# setting is 1.  To disable file uploads when creating or editing posts, set 
# this variable to 0.
#
# This is included by the new.php wrapper script for blogs.

session_start();
require_once("config.php");
require_once("lib/creators.php");
require_once("xmlrpc/xmlrpc.inc");
require_once("pages/pagelib.php");

global $PAGE;

$blg = NewBlog();
$u = NewUser();
$ent = NewEntry();
$PAGE->setDisplayObject($ent);

$is_art = is_a($ent, 'Article');

$tpl = NewTemplate(ENTRY_EDIT_TEMPLATE);
entry_set_template($tpl, $ent);
$tpl->set("ALLOW_ENCLOSURE", $blg->allow_enclosure);
$tpl->set("BLOG_TAGS", $blg->tag_list);
$tpl->set("FORM_ACTION", make_uri(false,false,false) );
$blg->exportVars($tpl);

if (POST('submit')) {
	
	$err = false;
	
	if ($u->checkLogin() && $SYSTEM->canModify($ent, $u)) {
		
		$ent->getPostData();
		
		if ($ent->data) {
			$ret = $ent->update();
			if ($is_art) $ent->setSticky(POST('sticky'));
			$blg->updateTagList($ent->tags());
			
			if ($ret) {
				
				$messages = handle_uploads($ent);
				if (is_array($messages)) {
					$ret = false;
					$err = _("Upload errors:")."<br />".
					       implode("\n<br />", $messages);
				}
			
				# Check for pingback-enabled links and send them pings.
				if ($ret && $blg->auto_pingback) {
					$err = handle_pingback_pings($ent);
					if ($err) $ret = false;
				}
				                                  
			} else { 
				$err = _("Error: unable to update entry.");
			}
				
		} else {
			$err = _("Error: entry contains no data.");
		}
		
	} else {
		$err = spf_("Permission denied: user %s cannot update this entry.", $u->username());
	}
	
	if ($err) {
		$tpl->set("HAS_UPDATE_ERROR");
		$tpl->set("UPDATE_ERROR_MESSAGE", $err);
		entry_set_template($tpl, $ent);
	} else $PAGE->redirect($ent->permalink());
	
} elseif (POST('preview')) {
	
	$last_var = $is_art ? 'last_article' : 'last_blogentry';
	
	$blg->$last_var = $ent;
	
	$blg->$last_var->getPostData();
	$u->exportVars($tpl);
	$blg->raiseEvent($is_art?"OnArticlePreview":"OnEntryPreview");
	entry_set_template($tpl, $blg->$last_var);
	$tpl->set("PREVIEW_DATA", $blg->$last_var->get() );
}

$body = $tpl->process();
$title = $is_art ? 
         spf_("%s - Edit Entry", $blg->name) :
         spf_("%s - Edit Article", $blg->name);
$PAGE->title = 
$PAGE->addStylesheet("form.css", $is_art?"blogentry.css":"article.css");
$PAGE->addScript("editor.js");
$PAGE->display($body, &$blg);
?>
