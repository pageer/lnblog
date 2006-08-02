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

# File: newentry.php
# Used to create a new blog entry.  
# To edit or delete an entry, refer to the <editentry.php> and <delentry.php>
# files respectively.
#
# When creating entries, it is possible to include a file upload in the POST.
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

$is_art = GET('type')=='article';

$blg = NewBlog();
$u = NewUser();
$PAGE->setDisplayObject($blg);

$tpl = NewTemplate(ENTRY_EDIT_TEMPLATE);
if ($is_art) {
	$tpl->set("GET_SHORT_PATH");
	$tpl->set("STICKY", true);
	$tpl->set("COMMENTS", false);
	$tpl->set("TRACKBACKS", false);
}
$tpl->set("ALLOW_ENCLOSURE", $blg->allow_enclosure);
$tpl->set("FORM_ACTION", current_file() );
$tpl->set("HAS_HTML", $blg->default_markup);
$tpl->set("BLOG_TAGS", $blg->tag_list);
$blg->exportVars($tpl);

if (POST('submit')) {
	
	$err = false;
	
	if ($u->checkLogin() && $SYSTEM->canAddTo($blg, $u)) {
	
		if ($is_art) $ent = NewArticle();
		else $ent = NewBlogEntry();
		
		$ent->getPostData();
		
		if ($ent->data) {
			if ($is_art) $ret = $ent->insert($blg, POST('short_path'));
			else $ret = $ent->insert($blg);
			$blg->updateTagList($ent->tags());
			if ($is_art && POST('sticky')) $ent->setSticky(true);
			
			if ($ret) {
				
				$messages = handle_uploads($ent);
				if (is_array($messages)) {
					$ret = false;
					$err = _("Upload errors:")."<br />".
					       implode("\n<br />", $messages);
				}
				
				# Send pingbacks to any relevant links.
				# Should this go before the uploads or after?
				# And for that matter, does it even make a difference?
				if ($ret && $blg->auto_pingback) {
					$err = handle_pingback_pings($ent);
					if ($err) $ret = false;
				}
			
			} else { 
				$err = _("Error: unable to add entry.");
			}

		} else {
			$err = _("Error: entry contains no data.");
		}
		
	} else {
		$err = spf_("Permission denied: user %s cannot add entries to this blog.", $u->username());
	}
	
	if ($err) {
		$tpl->set("HAS_UPDATE_ERROR");
		$tpl->set("UPDATE_ERROR_MESSAGE", $err);
		entry_set_template($tpl, $ent);
	} else $PAGE->redirect($ent->permalink());
	
} elseif (POST('preview')) {
	
	$last_var = $is_art ? 'last_article' : 'last_blogentry';
	
	if ($is_art) $blg->$last_var = NewBlogEntry();
	else $blg->$last_var = NewArticle();
	
	$blg->$last_var->getPostData();
	$u->exportVars($tpl);
	$blg->raiseEvent($is_art?"OnArticlePreview":"OnEntryPreview");
	entry_set_template($tpl, $blg->$last_var);
	$tpl->set("PREVIEW_DATA", $blg->$last_var->get() );
}

$body = $tpl->process();
$title = $is_art ? 
         spf_("%s - New Entry", $blg->name) :
         spf_("%s - New Article", $blg->name);
$PAGE->title = $title;
$PAGE->addStylesheet("form.css", $is_art?"blogentry.css":"article.css");
$PAGE->addScript("editor.js");
$PAGE->display($body, &$blg);
?>
