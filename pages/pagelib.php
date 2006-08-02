<?php

# File: pagelib.php
# This file is a library of routines that are shared among several pages.  
# It is in the pages directory rather than the lib directory because the
# functions here are front-end code, not back-end stuff.  For example, this 
# file contains the shared function for adding a (working) comment form to the 
# page.

# Function: handle_comment
# Handles comments posted to an entry.  This includes generating the form 
# markup, setting appropriate cookies, and actually inserting the new comments.
# 
# Parameters:
# ent           - The entry we're dealing with.
# use_comm_link - *Optional* boolean, defaults to false.  If this is set to 
#                 true, then the page will be redirected to the comments page 
#                 after a successful comment post.  If not, then the redirect 
#                 will be to the entry permalink.
#
# Returns:
# The markup to be inserted into the page for the comment form.

function handle_comment(&$ent, $use_comm_link=false) {
	global $PAGE;
	global $SYSTEM;
		
	$PAGE->addStylesheet("form.css");
	$comm_tpl = NewTemplate(COMMENT_FORM_TEMPLATE);
	# Set form information saved in cookies.
	if (COOKIE('comment_url')) 
		$comm_tpl->set("COMMENT_URL", COOKIE('comment_url'));
	if (COOKIE('comment_name')) 
		$comm_tpl->set("COMMENT_NAME", COOKIE('comment_name'));
	if (COOKIE('comment_email'))
		$comm_tpl->set("COMMENT_EMAIL", COOKIE('comment_email'));
	if (COOKIE('comment_showemail'))
		$comm_tpl->set("COMMENT_SHOWEMAIL", COOKIE('comment_showemail'));
	$comm_tpl->set("FORM_TARGET", $ent->uri("basepage"));
	
	if (has_post()) {
		
		$cmt = NewBlogComment();
		$cmt->getPostData();
		
		$err = false;
		$ret = false;
		if ($cmt->data) { 
			# Filter out some obviouisly invalid data
			if ( strpos(POST('subject'), "\n") !== false ||
				 strpos(POST('username'), "\n") !== false ||
				 strpos(POST('email'), "\n") !== false ||
				 strpos(POST('url'), "\n") !== false ) {
				$err = _("Error: line breaks are only allowed in the comment body.  What are you, a spam bot?");
			} else {
				$ret = $cmt->insert($ent);
			}
		} else {
			$err = _("Error: you must include something in the comment body.");
		}
		
		if ($ret && !$err) {
			# If the "remember me" box is checked, save the info in cookies.
			if (POST("remember")) {
				$path = "/";  # Do we want to do daomain cookies?
				$exp = time()+2592000;  # Expire cookies after one month.
				if (POST("username"))
					setcookie("comment_name", POST("username"), $exp, $path);
				if (POST("email"))
					setcookie("comment_email", POST("email"), $exp, $path);
				if (POST("url"))
					setcookie("comment_url", POST("url"), $exp, $path);
				if (POST("showemail"))
					setcookie("comment_showemail", POST("showemail"), $exp, $path);
			}
			# Redirect to prevent double-posts.
			if ($use_comm_link) $PAGE->redirect($ent->uri('comment'));
			else $PAGE->redirect($ent->permalink());
		} else {
			# Set the data back in the form, along with error messages.
			$comm_tpl->set("COMMENT_DATA", POST('data'));
			$comm_tpl->set("COMMENT_SUBJECT", POST('subject'));
			$comm_tpl->set("COMMENT_URL", POST('url'));
			$comm_tpl->set("COMMENT_NAME", POST('username'));
			$comm_tpl->set("COMMENT_EMAIL", POST('email'));
			$comm_tpl->set("COMMENT_SHOWEMAIL", POST('showemail'));
			$comm_tpl->set("COMMENT_FORM_MESSAGE", $err ? $err :
						   _("Error: unable to add commtent please try again."));
		}
	}

	return $comm_tpl->process();

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
	global $SYSTEM;
	$err = array();
	$num_uploads = $SYSTEM->sys_ini->value("entryconfig",	"AllowInitUpload", 1);
	for ($i=1; $i<=$num_uploads; $i++) {
		$upld = NewFileUpload('upload'.$i, $ent->localpath());
		if ( $upld->completed() ) {
			$upld->moveFile();
		} elseif ($upld->status() != FILEUPLOAD_NO_FILE) {
			$ret = false;
			$err[] = $upld->errorMessage();
		}
	}
	
	if ($err) {
		return $err;
	} else {
		return true;
	}
}

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
	global $SYSTEM;
	
	if (! $ent->allow_pingback) return '';
	
	$local = $SYSTEM->sys_ini->value("entryconfig", "AllowLocalPingback", 1);
	$results = $ent->sendPings($local);
	$errors = array();
	$err = '';

	foreach ($results as $res) {
		if ($res['response']->faultCode()) {
			$errors[] = spf_('URI: %s', $res['uri']).'<br />'.
			            spf_("Error %d: %s", 
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

# Function: entry_set_template
# Sets variables in an entry template for display.
#
# Parameters:
# tpl - The template to populate.
# ent - the BlogEntry or Article with which to populate the template.

function entry_set_template(&$tpl, &$ent) {
	$tpl->set("URL", POST("short_path"));
	$tpl->set("SUBJECT", htmlentities($ent->subject));
	$tpl->set("TAGS", htmlentities($ent->tags));
	$tpl->set("DATA", htmlentities($ent->data));
	$tpl->set("ENCLOSURE", $ent->enclosure);
	$tpl->set("HAS_HTML", $ent->has_html);
	$tpl->set("COMMENTS", $ent->allow_comment);
	$tpl->set("TRACKBACKS", $ent->allow_tb);
	$tpl->set("PINGBACKS", $ent->allow_pingback);
	if (is_a($ent, 'Article')) {
		if ($ent->isEntry()) {
			$tpl->set("STICKY", $ent->isSticky());
		} else {
			$tpl->set("STICKY", (POST('sticky') ? true : false));
		}
	}
}

# Function: blog_set_template
# Sets template variables for display of a blog.
#
# Parameters:
# tpl - The template to populate.
# ent - the BlogEntry or Article with which to populate the template.

function blog_set_template(&$tpl, &$blog) {
	$tpl->set("BLOG_NAME", $blog->name);
	$tpl->set("BLOG_WRITERS", implode(",", $blog->writers() ) );
	$tpl->set("BLOG_DESC", $blog->description);
	$tpl->set("BLOG_IMAGE", $blog->image);
	$tpl->set("BLOG_THEME", $blog->theme);
	$tpl->set("BLOG_MAX", $blog->max_entries);
	$tpl->set("BLOG_RSS_MAX", $blog->max_rss);
	$tpl->set("BLOG_ALLOW_ENC", $blog->allow_enclosure);
	$tpl->set("BLOG_DEFAULT_MARKUP", $blog->default_markup);
	$tpl->set("BLOG_AUTO_PINGBACK", $blog->auto_pingback);
	$tpl->set("BLOG_GATHER_REPLIES", $blog->gather_replies);
}

# Function: blog_get_post_data
# Get data from the HTTP POST and put it into the blog object.
#
# Parameters:
# blog - The blog to populate.

function blog_get_post_data(&$blog) {
	$blog->name = POST("blogname");
	$blog->writers(POST("writelist") );
	$blog->description = POST("desc");
	$blog->image = POST("image");
	$blog->theme = POST("theme");
	$blog->max_entries = POST("maxent");
	$blog->max_rss = POST("maxrss");
	$blog->allow_enclosure = POST("allow_enc")?1:0;
	$blog->default_markup = POST("blogmarkup");
	$blog->auto_pingback = POST('pingback')?1:0;
	$blog->gather_replies = POST('replies')?1:0;
}
?>
