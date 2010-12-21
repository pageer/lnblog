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

# File: entryedit.php
# Used to create a new blog entry or article or to edit an existing one..  
# To delete an entry, refer to the <delentry.php> file.
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

# Function: handle_pingback_pings
# Handles pingbacks for an entry.  Sends pingbacks to the appropriate links
# in the entry body, and returns an error string, if applicable.
#
# Parameters:
# ent - The entry in question.
#
# Returns:
# An error string.  If there were no errors sending any pingbacks, then the 
# null string is returned.

function handle_pingback_pings(&$ent) {
	if (! $ent->allow_pingback) return '';
	
	$local = System::instance()->sys_ini->value("entryconfig", "AllowLocalPingback", 1);
	$results = $ent->sendPings($local);
	$errors = array();
	$err = '';

	foreach ($results as $res) {
		if ($res['response']->faultCode()) {
			$errors[] = spf_('URI: %s', $res['uri']).'<br />'.
			            spf_("Error %d: %s<br />", 
			                 $res['response']->faultCode(), 
			                 $res['response']->faultString());
		}
	}

	if ($errors) {
		$err = _("Failed to send the following pingbacks:").
		       '<br />'.implode("\n<br />", $errors);
	}
	return $err;
}

# Function: handle_uploads
# Handles uploads that are sent when an entry is edited.  It checks the file 
# uploads and moves them to the entry directory.
#
# Parameters:
# ent - The entry we're editing.
#
# Returns:
# If all uploads are successful, returns true.  Otherwise, returns an array
# of error messages, one element for each upload error.

function handle_uploads(&$ent) {
	$err = array();
	$num_uploads = System::instance()->sys_ini->value("entryconfig",	"AllowInitUpload", 1);
	
	$uploads = FileUpload::initUploads($_FILES['upload'], $ent->localpath());

	foreach ($uploads as $upld) {
		if ( $upld->completed() ) {
			$ret = $upld->moveFile();
			if (! $ret) {
				$err[] = _('Error moving uploaded file');
			}
		} elseif ( ( $upld->status() != FILEUPLOAD_NO_FILE && 
					 $upld->status() != FILEUPLOAD_NOT_INITIALIZED ) ||
				   ( $upld->status() == FILEUPLOAD_NOT_INITIALIZED &&
					! defined("UPLOAD_IGNORE_UNINITIALIZED") ) ) {
			$ret = false;
			$err[] = $upld->errorMessage();
		}
	}
	
	if ($err) {
		return $err;
	} else {
		# This event is raised here as sort of a hack.  The idea is that some
		# plugins will need information on uploaded files, but can only get that 
		# when an event is raised by the entry.
		# In particular, this intended to regenerate the RSS2 feed after uploading
		# a file from the edit form, so that the enclosure information will be 
		# set correctly.
		if (! $ent->isDraft()) $ent->raiseEvent("UpdateComplete");
		return true;
	}
}

# Function: handle_save
# Takes care of saving an entry and handling the uploads, if applicable.
#
# Parameters:
# ent - The current entry object.
# blg - The current blog object, i.e. the parent of ent.
# errors - Reference string parameter to return error messages generated by uploads.
#
# Returns:A boolean or numeric false on failure, non-false on success.
function handle_save(&$ent, &$blg, &$errors, $is_draft) {
	if ($is_draft) {
		$ret = $ent->saveDraft($blg);
	} else {
		if (! $ent->isEntry()) {
			if (is_a($ent, 'Article')) $ent->setPath(POST('short_path'));
			$ret = $ent->insert($blg);
			if ($ret && is_a($ent, 'Article')) $ent->setSticky(POST('sticky'));
		} elseif ($ent->isDraft()) {
			$ret = $ent->publishDraft($blg);
		} else {
			$ret = $ent->update();
		}
		
		if ($ret) $blg->updateTagList($ent->tags());
	}
	
	if ($ret) {
		$messages = handle_uploads($ent);
		if (is_array($messages)) {
			$ret = false;
			$err = _("File upload errors:")."<br />".
					implode("\n<br />", $messages);
			$errors = "<p>".$err."</p>";
		}
	}

	return $ret;
}

$PAGE = Page::instance();

$blg = NewBlog();
$u = NewUser();
$ent = NewEntry();

if ($ent === false) {
	$do_new = true;
	$PAGE->setDisplayObject($blg);
} else {
	$do_new = false;
	$PAGE->setDisplayObject($ent);
}

$is_art = ( GET('type')=='article' || is_a($ent, 'Article') );

$tpl = NewTemplate(ENTRY_EDIT_TEMPLATE);
if (! $do_new) {
	entry_set_template($tpl, $ent);
} elseif ($is_art) {
	$tpl->set("GET_SHORT_PATH");
	$tpl->set("STICKY", true);
	$tpl->set("COMMENTS", false);
	$tpl->set("TRACKBACKS", false);
	$tpl->set("PINGBACKS", false);
}

$tpl->set("ALLOW_ENCLOSURE", $blg->allow_enclosure);
sort($blg->tag_list);
$tpl->set("BLOG_TAGS", $blg->tag_list);

if ($do_new) {
	$tpl->set("SEND_PINGBACKS", $blg->auto_pingback != 'none');
	$tpl->set("HAS_HTML", $blg->default_markup);
} else {
	$tpl->set("SEND_PINGBACKS", $blg->auto_pingback == 'all');
}
$tpl->set("FORM_ACTION", make_uri(false,false,false) );
$blg->exportVars($tpl);

if ( POST('submit') || POST('draft') ) {
	
	$err = false;
	
	if ($u->checkLogin() && 
	    ( ($do_new && $SYSTEM->canAddTo($blg, $u)) ||
		  (! $do_new && $SYSTEM->canModify($ent, $u)) ) ) {
		
		if ($do_new) {
			$ent = $is_art ? NewArticle() : NewBlogEntry();
		}
		
		$ent->getPostData();
		
		if ($ent->data) {

			$page_body = '';
			$ret = handle_save($ent, $blg, $page_body, POST('draft'));
			
			if ($ret) {

				# Check for pingback-enabled links and send them pings.
				if ( $ret && POST("send_pingbacks") && ! POST('draft') ) {
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
				$err = _("Error: unable to update entry.");
			}
				
		} else {
			$err = _("Error: entry contains no data.");
		}
		
	} else {
		if ($do_new) {
			$ent = NewBlogEntry();
			$ent->getPostData();
		}
		$err = spf_("Permission denied: user %s cannot update this entry.", $u->username());
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
	
} elseif (POST('preview') || GET('preview')) {
	
	$last_var = $is_art ? 'last_article' : 'last_blogentry';
	
	if ($do_new) {
		if ($is_art) $blg->$last_var = NewArticle();
		else $blg->$last_var = NewBlogEntry();
	} else {
		$blg->$last_var = $ent;
	}
	
	$blg->$last_var->getPostData();
	
	if (isset($_GET['save']) && $_GET['save'] == 'draft') {
		$errs = '';
		$ret = handle_save($blg->$last_var, $blg, $errs, true);
		$uri = create_uri_object($blg->$last_var);
		$uri->separator = '&';
	#echo $uri->editDraft(true);
		$PAGE->redirect($uri->editDraft(true));
		exit;
	}
	
	$u->exportVars($tpl);
	$blg->raiseEvent($is_art?"OnArticlePreview":"OnEntryPreview");
	entry_set_template($tpl, $blg->$last_var);
	$tpl->set("PREVIEW_DATA", $blg->$last_var->get() );

} elseif ( empty($_POST) && ! $u->checkLogin() ) {
	header("HTTP/1.0 403 Forbidden");
	p_("Access to this page is restricted to logged-in users.");
	exit;
}

# Process the template into the page body, but only if we have not already set
# it.  We may set it above to display a message that does not constitute a 
# fatal error, such as a failed pingback.
if (empty($page_body)) $page_body = $tpl->process();

$title = $is_art ? _("New Article") : _("New Entry");
$PAGE->title = sprintf("%s - %s", $blg->name, $title);
$PAGE->addStylesheet("form.css", "entry.css");
$PAGE->addScript("editor.js");
$PAGE->addScript("upload.js");
$PAGE->addScript(lang_js());
$PAGE->display($page_body, $blg);
