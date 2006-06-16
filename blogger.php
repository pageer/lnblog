<?php
/*
File: blogger.php
This file implements the Blogger 1.0 API for posting to your blog via XML-RPC.

Section: Overview

Blogger 1.0 is an old (as blogging APIs go) and widely supported API
for use with weblog clients.  It allows you to add and edit blog posts as well
as set and retreive certain information about your blog.  Support for this API 
was added to LnBlog in version 0.7.0 beta 1.

The Blogger 1.0 API has six methods, listed below.

blogger.newPost       - Create a new blog entry.
blogger.editPost      - Modify the contents of an existing entry.
blogger.getUsersBlogs - Get the blogs for a particular user.
blogger.getUserInfo   - Get the user information for a particular user.
blogger.getTemplate   - Get the template used for the blog.
blogger.setTemplate   - Set the template used for the blog.

Section: Using the API with LnBlog

It is important to note that the Blogger 1.0 API is not generic.  That is, it was
designed specifically to work with Blogger and not for use by every weblog system
on the face of the Earth.  As a result, not all the methods work in exactly the 
same way as with Blogger.  In particular, the getTemplate and setTemplate are 
simply not applicable to the way LnBlog works, and so they are not implemented. 

In addition, the newPost and editPost methods do not include any metadata, and 
therefore do not accomodate setting a subject or topics for the entry.  To remedy
this, LnBlog allows you to (optionally) start your post with subject and tag 
lines, as indicated in the sample post data below.
|Subject: Hey, it's a subject!
|Tags: General,Test
|This is the body of the post.  The above two lines will be 
|stripped out of the post body and converted into the subject 
|and tags for this post.

Lastly, it should be noted that the API does not include a concept of input mode,
i.e. there is no facility to set HTML, BBCode, or simple text input.  Therefore,
LnBlog will assume that all posts made with API calls use the default markup 
mode for the current blog.

Section: Configuration

When configuring your blogging client to use Blogger 1.0 with LnBlog, give the
URL of your LnBlog/blogger.php file as the address to handle the requests.  You
can use your normal LnBlog username and password as your login.  For the blog ID,
give the root-relative path to your blog.  If you look on the <index.php> admin
page, this is simply the text that shows up in the drop-down for upgrading your
blog.  

When editing posts via the blogger API, the post ID is simply the URL of the 
directory in which the post is stored, with the protocol and domain name removed.
So, if your post is at 
|http://www.mysite.com/myblog/2006/05/04/03_2100/
then the post ID would be
|myblog/2006/05/04/03_2100/

*/
# Include the libraries for XMLRPC.
require_once("xmlrpc/xmlrpc.inc");
require_once("xmlrpc/xmlrpcs.inc");

require_once("blogconfig.php");
require_once("lib/creators.php");
	
$post_sig = array(array($xmlrpcString,$xmlrpcString,$xmlrpcString,
                        $xmlrpcString,$xmlrpcString,$xmlrpcBoolean));
$user_sig = array(array($xmlrpcString,$xmlrpcString,$xmlrpcString));
/*
$function_map = array(
	"blogger.newPost"       => array("function"=>"new_post",
	                                 "signature"=>$post_sig),
	"blogger.editPost"      => array("function"=>"edit_post",
	                                 "signature"=>$post_sig),
	"blogger.getUsersBlogs" => array("function"=>"get_user_blogs",
	                                 "signature"=>$user_sig),
	"blogger.getUserInfo"   => array("function"=>"get_user_info",
	                                 "signature"=>$user_sig),
	"blogger.getTemplate"   => array("function"=>"get_template"),
	"blogger.setTemplate"   => array("function"=>"set_template")
);
*/
$function_map = array(
	"blogger.newPost"       => array("function"=>"new_post"),
	"blogger.editPost"      => array("function"=>"edit_post"),
	"blogger.getUsersBlogs" => array("function"=>"get_user_blogs"),
	"blogger.getUserInfo"   => array("function"=>"get_user_info"),
	"blogger.getTemplate"   => array("function"=>"get_template"),
	"blogger.setTemplate"   => array("function"=>"set_template")
);

function new_post($params) {

	global $xmlrpcerruser;
	global $SYSTEM;
	
	$appkey = $params->getParam(0);  # We ignore the appkey.
	$blogid = $params->getParam(1);
	$username = $params->getParam(2);
	$password = $params->getParam(3);
	$content = $params->getParam(4);
	$publish = $params->getParam(5);  # The publish flag is also ignored.
	
	$blog = NewBlog($blogid->scalarval());
	
	$uid = $username->scalarval();
	$pwd = $password->scalarval();
	$usr = NewUser($uid);
	
	$ret = false;
	
	if ( $usr->checkPassword($pwd) &&
	     $SYSTEM->canAddTo($blog, $usr) ) {
		$ent = NewBlogEntry();
		$ent->subject = strftime("%d %B %Y");  # Set initial subject
		
		# Test for initial lines to set the subject and/or tags.
		$data = explode("\n", $content->scalarval());
		if (isset($data[0]) && preg_match("/^Subject:.+/i", trim($data[0]))) {
			$ent->subject = trim(preg_replace("/^Subject:(.+)/i", "$1", trim($data[0])));
			$data[0] = '';
		}
		if (isset($data[1]) && preg_match("/^Tags:.+/i", trim($data[1]))) {
			$ent->tags = trim(preg_replace("/^Tags:(.+)/i", "$1", trim($data[1])));
			$data[1] = '';
		}
		$data = implode("\n", $data);

		$ent->uid = $usr->username();
		$ent->data = $data;
		$ret = $ent->insert($blog);
		$blog->updateTagList($ent->tags());
		if ($ret) $ret = new xmlrpcresp( new xmlrpcval($ent->uri("base")) );
		else $ret = new xmlrpcresp(0, $xmlrpcerruser+2, "Entry add failed");
	} else {
		$ret = new xmlrpcresp(0, $xmlrpcerruser+3, "Invalid password - cannot create new post");
	 }
	return $ret;
}

function edit_post($params) {
	global $xmlrpcerruser;
	global $SYSTEM;
	
	$appkey = $params->getParam(0);  # We ignore the appkey.
	$postid = $params->getParam(1);
	$username = $params->getParam(2);
	$password = $params->getParam(3);
	$content = $params->getParam(4);
	$publish = $params->getParam(5);  # The publish flag is also ignored.
	
	$postpath = $postid->scalarval();
	if (PATH_DELIM != '/') $postpath = str_replace("/", PATH_DELIM, $postpath);
	$postpath = calculate_document_root().PATH_DELIM.$postpath;
	$ent = NewEntry($postpath);
	$blog = $ent->getParent();
	
	$uid = $username->scalarval();
	$pwd = $password->scalarval();
	$usr = NewUser($uid);
	
	$ret = false;
	
	if ( $usr->checkPassword($pwd) && $SYSTEM->canModify($ent, $usr) ) {


		# Test for initial lines to set the subject and/or tags.
		$data = explode("\n", $content->scalarval());

		if (isset($data[0]) && preg_match("/^Subject:.+/i", trim($data[0]))) {
			$ent->subject = trim(preg_replace("/^Subject:(.+)/i", "$1", trim($data[0])));
			$data[0] = '';
		}
		if (isset($data[1]) && preg_match("/^Tags:.+/i", trim($data[1]))) {
			$ent->tags = trim(preg_replace("/^Tags:(.+)/i", "$1", trim($data[1])));
			$data[1] = '';
		}

		$data = implode("\n", $data);

		if ($data) {
		
			$ent->data = $data;
			$ret = $ent->update();
			$blog->updateTagList($ent->tags());
			if ($ret) $ret = new xmlrpcresp( new xmlrpcval($ent->uri("base"), 'string') );
			else $ret = new xmlrpcresp(0, $xmlrpcerruser+2, "Entry update failed");
			
		} else {
			$ret = new xmlrpcresp(0, $xmlrpcerruser+4, "No data in message - cannot edit post");
		}

	} else {
		$ret = new xmlrpcresp(0, $xmlrpcerruser+3, "Invalid password - cannot edit post");
	 }
	return $ret;
}

function get_user_blogs($params) {
	global $xmlrpcerruser;
	global $SYSTEM;
	
	$appkey = $params->getParam(0);  # We ignore the appkey.
	$username = $params->getParam(1);
	$password = $params->getParam(2);
	
	$uid = $username->scalarval();
	$pwd = $password->scalarval();
	$usr = NewUser($uid);
	
	if ( $usr->checkPassword($pwd) ) {
		$blogs = $SYSTEM->getUserBlogs($usr);
		if (! empty($blogs)) {
			$resp_arr = array();
			foreach ($blogs as $blg) {
				$resp_arr[] = new xmlrpcval(
				              array("url"=> new xmlrpcval($blg->getURL()),
				                    "blogid"=>new xmlrpcval($blg->blogid),
				                    "blogName"=>new xmlrpcval($blg->name)),
										  "struct");
			}
			$ret = new xmlrpcresp(new xmlrpcval($resp_arr, "array"));
		} else {
			$ret = new xmlrpcresp(0,$xmlrpcerruser+4, "This user has no blogs");
		}

	} else {
		$ret = new xmlrpcresp(0,$xmlrpcerruser+3, "Invalid password");
	}
	return $ret;
}

function get_user_info($params) {
	global $xmlrpcerruser;
	global $SYSTEM;
	
	$appkey = $params->getParam(0);  # We ignore the appkey.
	$username = $params->getParam(1);
	$password = $params->getParam(2);

	$uid = $username->scalarval();
	$pwd = $password->scalarval();
	$usr = NewUser($uid);

	if ( $usr->checkPassword($pwd) ) {
		
		$space_pos = strpos($usr->name(), " ");

		# First, let's check for some common custom field names
		# for both the first and last names.  If we don't find anything,
		# then try to extrapolate from the real name.
		if (isset($usr->firstname)) {
			$fname = $usr->firstname;
		} elseif (isset($usr->first_name)) {
			$fname = $usr->first_name;
		} elseif (isset($usr->fname)) {
			$fname = $usr->fname;
		} else {
			if ( $space_pos > 0 ) {
				$fname = substr($usr->name(), 0, $space_pos);
			} else {
				$fname = $usr->name();
			}
		}

		# Here's the same for the last name.
		if (isset($usr->lastname)) {
			$lname = $usr->lastname;
		} elseif (isset($usr->last_name)) {
			$lname = $usr->last_name;
		} elseif (isset($usr->lname)) {
			$lname = $usr->lname;
		} else {
			if ( $space_pos > 0 ) {
				$lname = substr($usr->name(), $space_pos + 1);
			} else {
				$lname = $usr->name();
			}
		}
		
		# Let's try it for the nickname too.
		if (isset($usr->nickname)) {
			$nickname = $usr->nickname;
		} else {
			$nickname = $usr->name();
		}

		$user_arr = new xmlrpcval(
						array('nickname'=>new xmlrpcval($nickname, 'string'),
						      'userid'=>new xmlrpcval($uid, 'string'),
						      'url'=>new xmlrpcval($usr->homepage(), 'string'),
						      'email'=>new xmlrpcval($usr->email(), 'string'),
						      'lastname'=>new xmlrpcval($lname, 'string'),
						      'firstname'=>new xmlrpcval($fname, 'string')),
						'struct');
		$ret = new xmlrpcresp($user_arr);
	} else {
		$ret = new xmlrpcresp(0,$xmlrpcerruser+3, "Invalid password");
	}
	return $ret;
}

function get_template($params) {
	global $xmlrpcerruser;
	return new xmlrpcresp(0, $xmlrpcerruser+1, 
	                      "Method blogger.getTemplate not implemented.");
}
function set_template($params) {
	global $xmlrpcerruser;
	return new xmlrpcresp(0, $xmlrpcerruser+1, 
	                      "Method blogger.setTemplate not implemented.");
}

$server = new xmlrpc_server($function_map);

?>
