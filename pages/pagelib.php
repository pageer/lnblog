<?php

# File: pagelib.php
# This file is a library of routines that are shared among several pages.  
# It is in the pages directory rather than the lib directory because the
# functions here are front-end code, not bakc-end stuff.  For example, this 
# file contains the shared function for adding a (working) comment form to the 
# page.

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
?>
