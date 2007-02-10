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
# Used to create a new blog entry or article.  
# To edit or delete an entry, refer to the <editentry.php> and <delentry.php>
# files respectively.
#
# The three main input fields on this page are the entry subject, topics, and 
# the post itself.  When creating new articles, there is also a box for the 
# article path, which is the directory name in which th article will be stored.
#
# The topic box has a drop-down list of topics next to it.  This control uses 
# JavaScript such that selecting an item from the drop-down will add it to the
# comma-separated list of entry topics, provided it is not already in the list.
#
# The main post body is entered in the textarea.  Input is expected to be in the
# format given in the entry options below the textarea.  If the input mode is
# set to LBCode, then some simple editor buttons will be displayed just above 
# the textarea.  These use JavaScript to insert prompt for text and insert the 
# appropriate LBCode markup.  There is currently no included HTML post editor,
# but plugins for both the FCKeditor and TinyMCE JavaScript rich-text HTML 
# editors are available for download.
#
# There are a number of entry options which are initially hidden on the page.
# These include a file upload box, a podcast/enclosure URL box, a "sticky" 
# checkbox for articles, the markup mode box, checkboxes to allow posting of
# comments, TrackBacks, and Pingbacks to the entry, and finally a checkbox 
# determining whether to send Pingback pings after the entry is posted.  Default
# settings for most of these options are configured on the <updateblog.php> page.
#
# There are a few couple of settings in the <system.ini> file that are relevant 
# here.  The first is the AllowInitUpload setting, which determines the number 
# of file upload boxes that appear on the page.  The default setting is 1, but 
# it can be increased to allow multiple files to be uploaded.  To disable 
# uploading files from the new entry page, set this to 0.
#
# The second setting is AllowLocalPingbacks.  This setting determines whether or
# not Pingback pings should be sent to URLs on the same server.  This might be
# desirable as an easy way to back-link references to other entries on your 
# blog or other blogs on your server.  To disable this and only send pings to 
# remote servers, set this value to 0.

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
	$tpl->set("PINGBACKS", false);
}
$tpl->set("ALLOW_ENCLOSURE", $blg->allow_enclosure);
$tpl->set("FORM_ACTION", current_file() );
$tpl->set("HAS_HTML", $blg->default_markup);
# Send pingback unless they're turned off completely.
$tpl->set("SEND_PINGBACKS", $blg->auto_pingback != 'none');
sort($blg->tag_list);
$tpl->set("BLOG_TAGS", $blg->tag_list);
$blg->exportVars($tpl);

if ( POST('submit') || POST('draft') ) {
	
	$err = false;
	
	if ($u->checkLogin() && $SYSTEM->canAddTo($blg, $u)) {
	
		if ($is_art) $ent = NewArticle();
		else $ent = NewBlogEntry();
		
		$ent->getPostData();
		
		if ($ent->data) {
			if (POST('draft')) {
				$ret = $ent->saveDraft($blg);
			} else {
				if ($is_art) $ret = $ent->insert($blg, POST('short_path'));
				else $ret = $ent->insert($blg);
				$blg->updateTagList($ent->tags());
			}
			
			if ($is_art && POST('sticky')) $ent->setSticky(true);
			
			if ($ret) {
				
				$messages = handle_uploads($ent);
				if (is_array($messages)) {
					$ret = false;
					$err = _("File upload errors:")."<br />".
					       implode("\n<br />", $messages);
					$page_body = "<p>".$err."</p>";
				}
				
				# Send pingbacks to any relevant links.
				# Should this go before the uploads or after?
				# And for that matter, does it even make a difference?
				if ($ret && POST('send_pingbacks')) {
					$err = handle_pingback_pings($ent);
					if (! isset($page_body)) $page_body = '';
					$page_body .= "<p>".$err."</p>";
				}
				
				if (isset($page_body)) {
					$refresh_delay = 10;
					$page_body = "<h4>"._("Entry created, but with errors")."</h4>".
					        $page_body."\n<p>".
							spf_('You will be redirected to <a href="%s">the new entry</a> in %d seconds.',
							     $ent->permalink(), $refresh_delay)."</p>";
					$PAGE->refresh($ent->permalink(), $refresh_delay);
				}
			
			} else { 
				$err = _("Error: unable to add entry.");
			}

		} else {
			$err = _("Error: entry contains no data.");
		}
		
	} else {
		$ent = NewBlogEntry();
		$ent->getPostData();
		$err = spf_("Permission denied: user %s cannot add entries to this blog.", $u->username());
	}
	
	if ($err) {
		$tpl->set("HAS_UPDATE_ERROR");
		$tpl->set("UPDATE_ERROR_MESSAGE", $err);
		entry_set_template($tpl, $ent);
	} elseif ( POST('draft') ) {
		$PAGE->redirect($blg->uri('listdrafts'));
	} else {
		$PAGE->redirect($ent->permalink());
	}
	
} elseif (POST('preview')) {
	
	$last_var = $is_art ? 'last_article' : 'last_blogentry';
	
	if ($is_art) $blg->$last_var = NewArticle();
	else $blg->$last_var = NewBlogEntry();
	
	$blg->$last_var->getPostData();
	$u->exportVars($tpl);
	$blg->raiseEvent($is_art?"OnArticlePreview":"OnEntryPreview");
	entry_set_template($tpl, $blg->$last_var);
	$tpl->set("PREVIEW_DATA", $blg->$last_var->get() );
}

# Process the template into the page body, but only if we have not already set
# it.  We may set it above to display a message that does not constitute a 
# fatal error, such as a failed pingback.
if (! isset($page_body)) $page_body = $tpl->process();

$title = $is_art ? 
         spf_("%s - New Article", $blg->name) :
         spf_("%s - New Entry", $blg->name);
$PAGE->title = $title;
$PAGE->addStylesheet("form.css","entry.css");
$PAGE->addScript("editor.js");
$PAGE->display($page_body, &$blg);
?>
