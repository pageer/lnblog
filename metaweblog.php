<?php
/*
File: metaweblog.php
This file implements the MetaWeblog API for posting to your blog via XML-RPC.

Section: Overview

The MetaWeblog API is a blogging interface that includes support for various 
types of blog metadata.  This is in contrast to the Blogger 1 API, which included
basic support for adding and editing entry text, but had no notion of subjects, 
categories, and the like.  It was also heavily biased in favor of Blogger's 
implementation.  The MetaWeblog API is intended as a more general API to remedy
this situation.

Like the Blogger API, MetaWeblog functions by making XML-RPC calls.  Most of the
data used by the API calls takes the form of XML-RPC structs.  The API 
contains the base methods given below.  For full details, please consult the
MetaWeblog API specification at <http://www.xmlrpc.com/metaWeblogApi>.

metaWeblog.newPost        - Creates a new post.
metaWeblog.editPost       - Edits an existing post.
metaWeblog.gePost         - Returns a representation of an existing post.
metaWeblog.newMediaObject - Creates a new image, video file, etc. for the blog.
metaWeblog.getCategories  - Returns the categories known to the blog.
metaWeblog.getRecentPosts - Returns a list of the most recently made posts.

Section: Configuration

When configuring your blogging client to use the MetaWeblog API with LnBlog, give the
URL of your LnBlog/metaweblog.php file as the address to handle the requests.  You
can use your normal LnBlog username and password as your login.  For the blog ID,
give the root-relative path to your blog.  If you look on the <index.php> admin
page, this is simply the text that shows up in the drop-down for upgrading your
blog.  

When editing posts via the API, the post ID is simply the URL of the 
directory in which the post is stored, with the protocol and domain name removed.
So, if your post is at 
|http://www.mysite.com/myblog/2006/05/04/03_2100/
then the post ID would be
|myblog/2006/05/04/03_2100/

Section: API Extensions

LnBlog's implementation conservatively extends the MetaWeblog API.  In other 
words, the implementation remains compatible with the standard, but adds a few
features that clients may, at their option, choose to use.

The newMediaObject method has been extended with an optional struct field called
'entryid'.  This field takes the same entry ID used by the getPost and editPost
methods.  If this field is specified, then the media object will be added to that
particular entry rather than to the base weblog.  Note that this extension only
makes sense for blog systems which can segregate files on a per-entry basis,
like LnBlog.  Systems that do not have such a concept should ignore this field.

*/
# Include the libraries for XMLRPC.
require_once("xmlrpc/xmlrpc.inc");
require_once("xmlrpc/xmlrpcs.inc");

require_once("blogconfig.php");
require_once("lib/creators.php");
	
# New/edit post signature: blogid, user, password, data struct, publish
$new_post_sig = array(array($xmlrpcString,$xmlrpcString,$xmlrpcString,
                            $xmlrpcStruct,$xmlrpcBoolean));
# Get post/categories signatures: postid, user, password
$get_sig = array(array($xmlrpcString,$xmlrpcString,$xmlrpcString));
# New media object sig: blogid, user, password, data struct
$media_sig = array(array($xmlrpcString,$xmlrpcString,$xmlrpcString, $xmlrpcStruct));
# Get recent posts sig: blogid, user, password, number of posts
$recent_sig = array(array($xmlrpcString,$xmlrpcString,$xmlrpcString, $xmlrpcInt));

$function_map = array(
	"metaWeblog.newPost"        => array("function"=>"new_post"),
	"metaWeblog.editPost"       => array("function"=>"edit_post"),
	"metaWeblog.getPost"        => array("function"=>"get_post"),
	"metaWeblog.newMediaObject" => array("function"=>"new_media"),
	"metaWeblog.getCategories"  => array("function"=>"get_categories"),
	"metaWeblog.getRecentPosts" => array("function"=>"get_recent")
);

# Method: metaWeblog.newPost
# Creates a new post.
#
# Parameters:
# blogid(string)   - Identifier for the blog.
# username(string) - Username to log in as.
# password(string) - The password to log in with.
# content(struct)  - A struct containing the post information.  The struct 
#                    members are, in general, the same as in the RSS 2.0 items.
# publish(boolean) - Whether or not to immediately publish the entry.
#                    The parameter is not currently used by LnBlog.
#
# Returns:
# A string representation of the unique ID of this post.

function new_post($params) {

	global $xmlrpcerruser;
	global $SYSTEM;
	
	$blogid = $params->getParam(0);
	$username = $params->getParam(1);
	$password = $params->getParam(2);
	$content = $params->getParam(3);
	$publish = $params->getParam(4);  # The publish flag is ignored.
	
	$blog = NewBlog($blogid->scalarval());
	
	$uid = $username->scalarval();
	$pwd = $password->scalarval();
	$usr = NewUser($uid);
	
	$ret = false;
	
	if ( $usr->checkPassword($pwd) &&
	     $SYSTEM->canAddTo($blog, $usr) ) {
		
		$ent = NewBlogEntry();
		$ent->subject = strftime("%d %B %Y");  # Set initial subject
			 
		while ($list = $content->structeach()) {
			
			# We only handle a few of the possible RSS2 item elements because
			# most of them only apply to already published entries.
			switch($list['key']) {
				case 'title':
					$ent->subject = $list['value']->scalarval();
					break;
				case 'description':
					$ent->data = $list['value']->scalarval();
					break;
				case 'category':
					$tag_arr = array();
					$size = $list['value']->arraysize();
					for ($i=0; $i < $size; $i++) {
						$elem = $list['value']->arraymem($i);
						$tag_arr[] = $elem->scalarval();
					}
					$ent->tags = implode(',', $tag_arr);
					break;
			}
			
		}

		$ent->uid = $usr->username();
		$ret = $ent->insert($blog);
		$blog->updateTagList($ent->tags());
		if ($ret) $ret = new xmlrpcresp( new xmlrpcval($ent->globalID()) );
		else $ret = new xmlrpcresp(0, $xmlrpcerruser+2, "Entry add failed");
	} else {
		$ret = new xmlrpcresp(0, $xmlrpcerruser+3, "Invalid login - cannot create new post");
	 }
	return $ret;
}

# Method: metaWeblog.editPost
# Change an existing post.
#
# Parameters:
# postid(string)   - Identifier for the post.
# username(string) - Username to log in as.
# password(string) - The password to log in with.
# content(struct)  - A struct containing the new post information.  The struct 
#                    members are, in general, the same as in the RSS 2.0 items.
# publish(boolean) - Whether or not to immediately publish the entry.
#                    The parameter is not currently used by LnBlog.
#
# Returns:
# True on success, raises a fault on failure.

function edit_post($params) {

	global $xmlrpcerruser;
	global $SYSTEM;
	
	$postid = $params->getParam(0);
	$username = $params->getParam(1);
	$password = $params->getParam(2);
	$content = $params->getParam(3);
	$publish = $params->getParam(4);  # The publish flag is ignored.
	
	$uid = $username->scalarval();
	$pwd = $password->scalarval();
	$usr = NewUser($uid);
	
	$postpath = $postid->scalarval();
	if (PATH_DELIM != '/') $postpath = str_replace("/", PATH_DELIM, $postpath);
	$postpath = calculate_document_root().PATH_DELIM.$postpath;
	
	$ret = false;
	
	$ent = NewBlogEntry($postpath);
	$blog = $ent->getParent();
	
	if ( $usr->checkPassword($pwd) &&
	     $SYSTEM->canModify($ent, $usr) ) {
			 
		while ($list = $content->structeach()) {
			
			# We only handle a few of the possible RSS2 item elements because
			# most of them only apply to already published entries.
			switch($list['key']) {
				case 'title':
					$ent->subject = $list['value']->scalarval();
					break;
					case 'description':
					$ent->data = $list['value']->scalarval();
					break;
				case 'category':
					$tag_arr = array();
					$size = $list['value']->arraysize();
					for ($i=0; $i < $size; $i++) {
						$elem = $list['value']->arraymem($i);
						$tag_arr[] = $elem->scalarval();
					}
					$ent->tags = implode(',', $tag_arr);
					break;
			}
			
		}

		$ret = $ent->update();
		$blog->updateTagList($ent->tags());
		if ($ret) $ret = new xmlrpcresp( new xmlrpcval(true,'boolean') );
		else $ret = new xmlrpcresp(0, $xmlrpcerruser+2, "Entry edit failed");
	} else {
		$ret = new xmlrpcresp(0, $xmlrpcerruser+3, "Invalid login - cannot edit this post");
	 }
	return $ret;
}

# Method: metaWeblog.getPost
# Get information for an existing post
#
# Parameters:
# postid(string)   - Identifier for the post.
# username(string) - Username to log in as.
# password(string) - The password to log in with.
#
# Returns:
# A struct representing the post.  As in the aruguments to <metaWeblog.newPost>,
# the struct contains elements corresponding to those in RSS 2.0 item elements.

function get_post($params) {
	global $xmlrpcerruser;
	global $SYSTEM;
	
	$postid = $params->getParam(0);
	$username = $params->getParam(1);
	$password = $params->getParam(2);
	
	$uid = $username->scalarval();
	$pwd = $password->scalarval();
	$usr = NewUser($uid);
	
	$postpath = $postid->scalarval();
	if (PATH_DELIM != '/') $postpath = str_replace("/", PATH_DELIM, $postpath);
	$postpath = calculate_document_root().PATH_DELIM.$postpath;
	
	$ret = false;
	
	# I think we can safely skip the permissions check here, since all the 
	# information is public anyway.  We'll just check for authentication
	# instead.
	if ( $usr->checkPassword($pwd) ) {
		
		$ent = NewBlogEntry($postpath);

		if ($ent->isEntry()) {
			$ret = new xmlrpcresp(entry_to_struct($ent));
		} else {
			$ret = new xmlrpcresp(0, $xmlrpcerruser+2, "Entry does not exist");
		}
	} else {
		$ret = new xmlrpcresp(0, $xmlrpcerruser+3, "Invalid login - cannot get this post");
	 }
	return $ret;
}

# New media objects are passed as structs, with 'name', 'type', and 'bits' 
# fields.  The 'bits' field is the base64-encoded data for the file.
# Method: metaWeblog.newMediaObject
# Uploads a file to the weblog over XML-RPC.
#
# Parameters:
# postid(string)   - Identifier for the post.
# username(string) - Username to log in as.
# password(string) - The password to log in with.
# content(struct)  - A struct containing the file information.  The struct must
#                    contain a 'name' field for the filename, a 'type' field for
#                    the file MIME type (LnBlog does not currently use this), and
#                    a 'bits' field that contains the base64-encoded file 
#                    content.  This implementation also accepts an 'entryid' 
#                    field, which contains the unique ID of an entry to which the
#                    file will be uploaded.  This only makes sense for blogging
#                    systems like LnBlog that allow per-entry uploads.
#
# Returns:
# A struct with a 'url' element that contains the HTTP or FTP URL to the file.

function new_media($params) {
	global $xmlrpcerruser;
	global $SYSTEM;
	
	$blogid = $params->getParam(0);
	$username = $params->getParam(1);
	$password = $params->getParam(2);
	$data = $params->getParam(3);
	
	$uid = $username->scalarval();
	$pwd = $password->scalarval();
	$usr = NewUser($uid);
	$blog = NewBlog($blogid->scalarval());
	
	if ( $usr->checkPassword($pwd) && $SYSTEM->canModify($blog, $usr) ) {
		
		$name = $data->structmem('name');
		$type = $data->structmem('type');
		$bits = $data->structmem('bits');
		@$ent = $data->structmem('entryid');

		if (! empty($ent)) {
			$postpath = $ent->scalarval();
			if (PATH_DELIM != '/') 
				$postpath = str_replace("/", PATH_DELIM, $postpath);
			$postpath = calculate_document_root().PATH_DELIM.$postpath;
			$entry = NewEntry($postpath);
			if ($entry->isEntry() && $SYSTEM->canModify($entry,$usr)) {
				$path = mkpath($entry->localpath(), $name->scalarval());
			} else {
				return new xmlrpcresp(0, $xmlrpcerruser+3, "Invalid login - cannot add files to this entry");
			}
		} else {
			$path = mkpath($blog->home_path, $name->scalarval());
		}
		$ret = write_file($path, base64_decode($bits->scalarval()));
		
		if ($ret) {
			$url = new xmlrpcval(localpath_to_uri($path), 'string');
			$ret = new xmlrpcresp(new xmlrpcval(array('url'=>$url), 'struct'));
		} else {
			$ret = new xmlrpcresp(0, $xmlrpcerruser+4, "Cannot create file $name");
		}
	} else {
		$ret = new xmlrpcresp(0, $xmlrpcerruser+3, "Invalid login - cannot add files to this blog");
	}
	return $ret;
}

# Method: metaWeblog.getCategories
# Gets a list of categories associated with a given blog.
#
# Parameters:
# blogid(string)   - Identifier for the blog.
# username(string) - Username to log in as.
# password(string) - The password to log in with.
#
# Returns:
# A struct containing one struct for each category.  The category structs must
# contain description, htmlURL, and rssURL elements.  Note that LnBlog currently
# does not have RSS URLs for categories.

function get_categories($params) {
	global $xmlrpcerruser;
	global $SYSTEM;
	
	$blogid = $params->getParam(0);
	$username = $params->getParam(1);
	$password = $params->getParam(2);
	
	$uid = $username->scalarval();
	$pwd = $password->scalarval();
	$usr = NewUser($uid);
	
	# Again, let's just skip the permissions check since this information is
	# public anyway.
	if ( $usr->checkPassword($pwd) ) {

		$blog = NewBlog($blogid->scalarval());
		
		if ($blog->isBlog()) {
			$arr = array();
			foreach ($blog->tag_list as $tag) {
				$cat = array();
				$cat['description'] = new xmlrpcval($tag, 'string');
				$cat['htmlURL'] = new xmlrpcval($blog->uri('tags').'?tag='.urlencode($tag), 'string');
				$cat['rssURL'] = new xmlrpcval('', 'string');
				$arr[$tag] = new xmlrpcval($cat, 'struct');
			}
			$ret = new xmlrpcresp(new xmlrpcval($arr, 'struct'));
		} else {
			$ret = new xmlrpcresp(0, $xmlrpcerruser+2, "Blog does not exist");
		}
	} else {
		$ret = new xmlrpcresp(0, $xmlrpcerruser+3, "Invalid login");
	 }
	return $ret;
}

# Method: metaWeblog.getRecentPosts
# Gets a list of the most recent posts to a blog.
#
# Parameters:
# blogid(string)     - Identifier for the blog.
# username(string)   - Username to log in as.
# password(string)   - The password to log in with.
# numberOfPosts(int) - The number of posts to return.
#
# Returns: 
# An array of structs.  The struct contents are as in the return value of the
# <metaWeblog.getPost> method.

function get_recent($params) {
	global $xmlrpcerruser;
	global $SYSTEM;
	
	$blogid = $params->getParam(0);
	$username = $params->getParam(1);
	$password = $params->getParam(2);
	$numposts = $params->getParam(3);
	
	$uid = $username->scalarval();
	$pwd = $password->scalarval();
	$usr = NewUser($uid);
	
	# Again, let's just skip the permissions check since this information is
	# public anyway.
	if ( $usr->checkPassword($pwd) ) {

		$blog = NewBlog($blogid->scalarval());
		
		if ($blog->isBlog()) {
			$entries = $blog->getRecent($numposts->scalarval());
			$arr = array();
			foreach ($entries as $ent) {
				$arr[] = entry_to_struct($ent);
			}
			$ret = new xmlrpcresp(new xmlrpcval($arr, 'array'));
		} else {
			$ret = new xmlrpcresp(0, $xmlrpcerruser+2, "Blog does not exist");
		}
	} else {
		$ret = new xmlrpcresp(0, $xmlrpcerruser+3, "Invalid login");
	 }
	return $ret;
}

# Takes a BlogEntry object and converts it into an XML-RPC struct.
function entry_to_struct(&$ent) {
	$arr = array();
	$user = NewUser($ent->uid);
	$arr['title'] = new xmlrpcval($ent->subject, 'string');
	$arr['link'] = new xmlrpcval($ent->permalink(), 'string');
	# This *should* return the user-entered text, right?
	$arr['description'] = new xmlrpcval($ent->data, 'string');
	$arr['author'] = new xmlrpcval($user->email(), 'string');
	
	$cat_list = array();
	$tags = $ent->tags();
	if (!$tags) $tags = array();
	foreach ($tags as $cat) $cat_list[] = new xmlrpcval($cat, 'string');
	$arr['category'] = new xmlrpcval($cat_list, 'array');
	
	$arr['guid'] = new xmlrpcval($ent->globalID(), 'string');
	$arr['pubDate'] = new xmlrpcval(date('r', $ent->post_ts), 'string');
	
	/*
	# On second thought, we don't really want to do this, because that's not 
	# what enclosures are supposed to be used for.
	$files = $ent->getUploadedFiles();
	foreach ($files as $f) {
		$encs = array();
		$path = mkpath($ent->localpath(), $f);
		$encs['url'] = $ent->uri('base').$f;
		$encs['length'] = filesize($path);
		if (extension_loaded("fileinfo")) {
			$mh = finfo_open(FILEINFO_MIME|FILEINFO_PRESERVE_ATIME);
			$encs['type'] = finfo_file($mh, $path);
		} else {
			$encs['type'] = mime_content_type($path);
		}
		$arr['enclosure'] = new xmlrpcval($encs, 'struct');
	}
	*/
	
	return new xmlrpcval($arr, 'struct');
}

$server = new xmlrpc_server($function_map);

?>
