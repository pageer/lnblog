<?php

# File: pagelib.php
# This file is a library of routines that are shared among several pages.  
# It is in the pages directory rather than the lib directory because the
# functions here are front-end code, not back-end stuff.  For example, this 
# file contains the shared function for adding a comment form to the page, 
# displaying lists of comments, and handling posted comments and files, 
# and so forth.

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
		
	Page::instance()->addStylesheet("form.css");
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
	
	if ($ent->getCommentCount() == 0) {
		if ($use_comm_link) $comm_tpl->set("PARENT_TITLE", trim($ent->subject));
		$comm_tpl->set("PARENT_URL", $ent->permalink());
	}
	
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
			if ($use_comm_link) Page::instance()->redirect($ent->uri('comment'));
			else Page::instance()->redirect($ent->permalink());
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

# Function: entry_set_template
# Sets variables in an entry template for display.
#
# Parameters:
# tpl - The template to populate.
# ent - the BlogEntry or Article with which to populate the template.

function entry_set_template(&$tpl, &$ent) {
	$tpl->set("URL", POST("short_path"));
	$tpl->set("SUBJECT", htmlspecialchars($ent->subject));
	$tpl->set("TAGS", htmlspecialchars($ent->tags));
	$tpl->set("DATA", htmlspecialchars($ent->data));
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
	$tpl->set("BLOG_FRONT_PAGE_ABSTRACT", $blog->front_page_abstract);
	$tpl->set("BLOG_MAIN_ENTRY", $blog->front_page_entry);
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
	$blog->auto_pingback = POST('pingback');
	$blog->gather_replies = POST('replies')?1:0;
	$blog->front_page_abstract = POST('use_abstract')?1:0;
	$blog->front_page_entry = POST('main_entry');
}

function show_comments(&$ent, &$usr, $sort_asc=true) {

	$title = spf_('Comments on <a href="%s">%s</a>', 
	              $ent->permalink(), htmlspecialchars($ent->subject));
#	$params = array('path'=>ENTRY_COMMENT_DIR, 'ext'=>COMMENT_PATH_SUFFIX, 'altext'=>'.txt',
#	                'creator'=>'NewBlogComment', 'sort_asc'=>$sort_asc,
#	                'itemclass'=>'comment', 'listtitle'=>$title,
#	                'typename'=>_("comments"));
	$cmts = $ent->getComments();
	return show_replies($ent, $usr, $cmts, $title);

}

function show_trackbacks(&$ent, &$usr, $sort_asc=true) {
	$title = spf_('Trackbacks on <a href="%s">%s</a>', 
	              $ent->permalink(), $ent->subject);
#	$params = array('path'=>ENTRY_TRACKBACK_DIR, 'ext'=>TRACKBACK_PATH_SUFFIX,
#	                'creator'=>'NewTrackback', 'sort_asc'=>$sort_asc,
#	                'itemclass'=>'trackback', 'listclass'=>'tblist',
#	                'listtitle'=>$title, 'typename'=>_("TrackBacks"));
	$tbs = $ent->getTrackbacks();
	return show_replies($ent, $usr, $tbs, $title);
}

/*
function show_pingbacks(&$ent, &$usr, $sort_asc=true, ) {
	$title = spf_('Pingbacks on <a href="%s">%s</a>', 
	              $ent->permalink(), $ent->subject);
	$params = array('path'=>ENTRY_PINGBACK_DIR, 'ext'=>PINGBACK_PATH_SUFFIX,
	                'creator'=>'NewPingback', 'sort_asc'=>$sort_asc,
	                'itemclass'=>'pingback', 'listclass'=>'pblist',
	                'listtitle'=>$title, 'typename'=>_("Pingbacks"));
	return show_replies($ent, $usr, $params);
}
*/

function show_pingbacks(&$ent, &$usr, $sort_asc=true) {
	$title = spf_('Pingbacks on <a href="%s">%s</a>', 
	              $ent->permalink(), $ent->subject);
	
#	$params = array('path'=>ENTRY_PINGBACK_DIR, 'ext'=>PINGBACK_PATH_SUFFIX,
#	                'creator'=>'NewPingback', 'sort_asc'=>$sort_asc,
#	                'itemclass'=>'pingback', 'listclass'=>'pblist',
#	                'listtitle'=>$title, 'typename'=>_("Pingbacks"));
	$pbs = $ent->getPingbacks();
	return show_replies($ent, $usr, $pbs, $title);
}

function show_remote_pingbacks(&$ent, &$usr, $sort_asc=true) {
	
}

function show_friendly_pingbacks(&$ent, &$usr, $sort_asc=true) {
	
}

function reply_boxes($idx, &$obj) {
	# Not sure if this should include a descriptive message or picture...
	return '<span>'.
	       '<input type="hidden" '.
	       'name="responseid'.$idx.'" id="'.get_class($obj).'id'.$idx.'" '.
	       #'value="'.$obj->getAnchor().'" />'.
		   'value="'.$obj->globalID().'" />'.
	       '<input type="checkbox" name="response'.$idx.'" '.
	       'id="'.get_class($obj).$idx.'" class="markbox" /></span>';
}

# Function: show_replies
# Gets the HTML for replies of the given type in a list.
#
# Parameters:
# ent     - A reference to the entry for which to get replies.
# usr     - A reference to the current user.
# replies - An array of reply objects.

function show_replies(&$ent, &$usr, &$replies, $title) {

	$ret = "";
	$count = 1;
	if ($replies) {
		$reply_text = array();
		$count = 0;
		foreach ($replies as $reply) {
			if (! isset($reply_type)) $reply_type = get_class($reply);
			$tmp = $reply->get();
			if (System::instance()->canModify($reply, $usr)) {
				$count += 1;
				$tmp = reply_boxes($count, $reply).$tmp;
			}
			$reply_text[] = $tmp;
		}
	}

	# Suppress markup entirely if there are no replies of the given type.
	if (isset($reply_text)) {

		$tpl = NewTemplate(LIST_TEMPLATE);
		
		if (System::instance()->canModify($reply, $usr)) {
			
			$typename = '';
			
			$tpl->set("FORM_HEADER", "<p>".
			          spf_("Delete marked %s", get_class($replies[0])).' '.
			          '<input type="submit" value="'._("Delete").'" />'.
			          '<input type="button" value="'._("Select all").
			          '" onclick="mark_type(\''.$reply_type.'\')" />'.
			          '<input type="hidden" name="replycount" value="'.
			          count($reply_text).'" />'."</p>\n");
			
			$blog = $ent->getParent();
			$qs = array('blog'=>$blog->blogid);
			if ($ent->isEntry()) $qs['entry'] = $ent->entryID();
			else $qs['article'] = $ent->entryID();
			$url = make_uri(INSTALL_ROOT_URL."pages/delcomment.php", $qs);
			$tpl->set("FORM_ACTION", $url);
		}
		
		$tpl->set("ITEM_CLASS", strtolower(get_class($replies[0])));
		#if (isset($params['listclass'])) {
		#	$tpl->set("LIST_CLASS", $params['listclass']);
		#}
		$tpl->set("ORDERED");
		$tpl->set("LIST_TITLE", $title);
		$tpl->set("ITEM_LIST", $reply_text);
		$ret = $tpl->process();
	}

	return $ret;

}

function show_all_replies(&$ent, &$usr) {

	# Get an array of each kind of reply.
	$pingbacks = $ent->getReplyArray(
		array('path'=>ENTRY_PINGBACK_DIR, 'ext'=>PINGBACK_PATH_SUFFIX,
		      'creator'=>'NewPingback', 'sort_asc'=>true));
	$trackbacks = $ent->getReplyArray(
		array('path'=>ENTRY_TRACKBACK_DIR, 'ext'=>TRACKBACK_PATH_SUFFIX,
		      'creator'=>'NewTrackback', 'sort_asc'=>true));
	$comments = $ent->getReplyArray(
		array('path'=>ENTRY_COMMENT_DIR, 'ext'=>COMMENT_PATH_SUFFIX,
		      'creator'=>'NewBlogComment', 'sort_asc'=>true));
			  
	# Merge the arrays and sort entries based on the ping_date and/or timestamp.
	$replies = array_merge($pingbacks, $trackbacks, $comments);
	usort($replies, 'reply_compare');
			  
	$ret = "";
	$count = 1;
	if ($replies) {
		$reply_text = array();
		$count = 0;

		foreach ($replies as $reply) {
			$tmp = $reply->get();
			if (System::instance()->canModify($reply, $usr)) {
				$count += 1;
				$tmp = reply_boxes($count, $reply).$tmp;
			}
			$reply_text[] = $tmp;
		}
	}

	# Suppress markup entirely if there are no replies of the given type.
	if (isset($reply_text)) {

		$tpl = NewTemplate(LIST_TEMPLATE);
		
		if (System::instance()->canModify($reply, $usr)) {
			$tpl->set("FORM_HEADER", 
			          spf_("<p>Delete marked replies %s</p>", 
			          '<input type="submit" value="'._("Delete").'" />'.
					  '<input type="button" value="'._("Select all").'" onclick="mark_all();" />'.
			          '<input type="hidden" name="replycount" value="'.
			          count($reply_text).'" />'));
			
			$blog = $ent->getParent();
			$qs = array('blog'=>$blog->blogid);
			if ($ent->isEntry()) $qs['entry'] = $ent->entryID();
			else $qs['article'] = $ent->entryID();
			$url = make_uri(INSTALL_ROOT_URL."pages/delcomment.php", $qs);
			$tpl->set("FORM_ACTION", $url);
		}
		
		$tpl->set("ITEM_CLASS", 'reply');
		$tpl->set("LIST_CLASS", 'replylist');
		$tpl->set("ORDERED");
		$tpl->set("LIST_TITLE", spf_('Replies on <a href="%s">%s</a>', 
	              $ent->permalink(), $ent->subject));
		$tpl->set("ITEM_LIST", $reply_text);
		$ret = $tpl->process();
	}

	return $ret;
}

function reply_compare(&$a, &$b) {
	if (isset($a->timestamp)) {
		$a_ts = $a->timestamp;
	} else {
		$a_ts = strtotime($a->ping_date);
	}
	
	if (isset($b->timestamp)) {
		$b_ts = $b->timestamp;
	} else {
		$b_ts = strtotime($b->ping_date);
	}
	
	if ($a_ts < $b_ts) return -1;
	elseif ($a_ts == $b_ts) return 0;
	else return 1;
}

###################################
# Common code for mass deletions. #
###################################

# Convenience function to get markup for an HTML list.
function get_list_text(&$obj) {
	if (! is_object($obj)) {
		return '<li>'.$obj."</li>\n";
	} else {
		return '<li>'.$obj->getAnchor().
		       ' - <a href="'.$obj->permalink().'">'.
		       $obj->title()."</a></li>\n";
	}
}

# Perform deletion on an array of object.  Returns an array of objects for which
# the deletion failed.
function do_delete(&$obj_arr) {
	$ret = array();
	foreach ($obj_arr as $resp) {
		$status = $resp->delete();
		if (! $status) $ret[] = $resp;
	}
	return $ret;
}

# Convert anchor names to objects and check the delete permissions on them.
# Returns false if the conversion or security check fails.
function get_response_object($anchor, &$usr) {

	if ( preg_match('/^comment/', $anchor) ) {
		$ret = NewBlogComment($anchor);
		if (! $ret->isComment()) $ret = false;
	} elseif ( preg_match('/^trackback/', $anchor) ) {
		$ret = NewTrackback($anchor);
		if (! $ret->isTrackback()) $ret = false;
	} elseif ( preg_match('/^pingback/', $anchor) ) {
		$ret = NewPingback($anchor);
		if (! $ret->isPingback()) $ret = false;
	} else {
		$ret = NewReply($anchor);
	}

	# If ret is a valid object, but usr doesn't have delete permission, then
	# return false.
	if ( $ret && ! System::instance()->canDelete($ret, $usr) ) $ret = false;

	return $ret;
}

?>
