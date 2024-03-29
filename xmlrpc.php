<?php
# File: xmlrpc.php
# This file includes all the XML-RPC server code for LnBlog.  This encompasses
# both the Pingback implementation and the Blogger and MetaWeblog API
# implementations.

require_once __DIR__."/blogconfig.php";
require_once __DIR__."/lib/creators.php";
require_once __DIR__."/lib/utils.php";
require_once __DIR__.'/vendor/phpxmlrpc/phpxmlrpc/lib/xmlrpc.inc';
require_once __DIR__.'/vendor/phpxmlrpc/phpxmlrpc/lib/xmlrpcs.inc';
require_once __DIR__.'/vendor/phpxmlrpc/phpxmlrpc/lib/xmlrpc_wrappers.inc';

use LnBlog\Tasks\TaskManager;
use LnBlog\Notifications\Notifier;

$function_map = array(
    "blogger.newPost"       => array("function"=>"blogger_newPost"),
    "blogger.editPost"      => array("function"=>"blogger_editPost"),
    "blogger.getUsersBlogs" => array("function"=>"blogger_getUsersBlogs"),
    "blogger.getUserInfo"   => array("function"=>"blogger_getUserInfo"),
    "blogger.getTemplate"   => array("function"=>"blogger_getTemplate"),
    "blogger.setTemplate"   => array("function"=>"blogger_setTemplate"),
    "blogger.deletePost"    => array("function"=>"blogger_deletePost"),
    "metaWeblog.newPost"        => array("function"=>"metaWeblog_newPost"),
    "metaWeblog.editPost"       => array("function"=>"metaWeblog_editPost"),
    "metaWeblog.getPost"        => array("function"=>"metaWeblog_getPost"),
    "metaWeblog.newMediaObject" => array("function"=>"metaWeblog_newMediaObject"),
    "metaWeblog.getCategories"  => array("function"=>"metaWeblog_getCategories"),
    "metaWeblog.getRecentPosts" => array("function"=>"metaWeblog_getRecentPosts"),
    'metaWeblog.deletePost'     => array("function"=>"blogger_deletePost"),
    'metaWeblog.getTemplate'    => array("function"=>"blogger_getTemplate"),
    'metaWeblog.setTemplate'    => array("function"=>"blogger_setTemplate"),
    'metaWeblog.getUsersBlogs'  => array("function"=>"blogger_getUsersBlogs"),
    'mt.getRecentPostTitles'  => array("function"=>"mt_getRecentPostTitles"),
    'mt.getCategoryList'      => array("function"=>"mt_getCategoryList"),
    'mt.getPostCategories'    => array("function"=>"mt_getPostCategories"),
    'mt.setPostCategories'    => array("function"=>"mt_setPostCategories"),
    'mt.supportedMethods'     => array("function"=>"mt_supportedMethods"),
    'mt.supportedTextFilters' => array("function"=>"mt_supportedTextFilters"),
    'mt.getTrackbackPings'    => array("function"=>"mt_getTrackbackPings"),
    'mt.publishPost'          => array("function"=>"mt_publishPost"),
    "pingback.ping"                    => array("function"=>"get_ping"),
    "pingback.extensions.getPingbacks" => array("function"=>"get_pingbacks")
);

/* *****************************************
Section: Pingback
This section implements Pingback for LnBlog, as described by the Pingback
specification at <http://hixie.ch/specs/pingback/pingback>.

Pingback is similar to Trackback in that it is a mechanism for one blog to
notify another when the first blog links to it.  However, Pingback uses XML-RPC
to send pings rather than HTTP POSTs.  Additionally, the Pingback enables
auto-discovery of pingable resources using HTTP headers and HTML link elements,
as opposed to the embedded RDF code used by Trackback.

The XML-RPC interface for Pingback consists of a single pingback.ping method
which is used to send a ping.  In addition, LnBlog implements the suggested
pingback.extensions.getPingbacks method used to syndicate pingbacks.
************************************* */

# Method: pingback.ping
# This method receives a ping from a remote host.  It maps the target URL to a
# blog object, validates the source URL, and stores the ping if appropriate.
#
# Parameters:
# sourceuri - The URI of the page doing the pinging.
# targeturi - The URI of the page being pinged.
#
# Returns:
# On success, the URL to the stored pingback.
function get_ping($params) {
    # URI of the linking page sent by the client.
    $sourceURI = $params->getParam(0);
    $sourceURI = $sourceURI->scalarval();
    # The URI of the page expected to be on this server.
    $targetURI = $params->getParam(1);
    $targetURI = $targetURI->scalarval();

    $matches = array();
    $ent = get_entry_from_uri($targetURI);
    if ($ent === false) {
        return new xmlrpcresp(0, 33, "Target URI not recognized.");
    }

    if ($ent->isEntry() && $ent->allow_pingback) {

        if ($ent->pingExists($sourceURI)) {
            return new xmlrpcresp(0, 48, "A Pingback for this URI has already been registered.");
        }

        $ping = new Pingback();

        $client = new HttpClient();
        $content = $client->fetchUrl($sourceURI, false);
        if (! $content) {
            return new xmlrpcresp(0, 16, "Unable to read source URI.");
        } elseif (! strpos($content, $targetURI)) {
            return new xmlrpcresp(0, 17, "Source URI does not link to target URI.");
        } else {

            $ret = preg_match('|.*<title>(.+)</title>.*|i', $content, $matches);
            $title = $ret ? trim($matches[1]) : '';

            $ping->source = $sourceURI;
            $ping->target = $targetURI;
            $ping->title = $title;
            $ping->excerpt = '';

            $lines = preg_split("/<p>|\n|<br \>|<br>/i", $content);
            foreach ($lines as $line) {
                $url_pos = strpos($line, $targetURI);
                if ($url_pos) {
                    $ping->excerpt = strip_tags($line);
                    break;
                }
            }

            $ret = $ping->insert($ent);

            if ($ret) {
                return new xmlrpcresp(new xmlrpcval($ping->permalink(), 'string'));
            } else {
                return new xmlrpcresp(0, 0, "Unable to record this ping.");
            }

        }
    }
    return new xmlrpcresp(0, 33, "Target URI not recognized or does not support pingbacks.");
}

# Method: pingback.extensions.getPingbacks
# Gets a list of pingbacks on a page.
#
# Parameters:
# url - The URL of the page in question.
#
# Returns:
# An array of URLs, one for each pingback registered for the target.
function get_pingbacks($params) {
    $url = $params->getParam(0);
    $url = $url->scalarval();

    $matches = array();
    $ent = get_entry_from_uri($url);
    if ($ent === false || ! $ent->isEntry() ) {
        $ret = new xmlrpcresp(0, 33, "Target URI not recognized.");
    } else {
        $pings = $ent->getPingbackArray();
        $arr = array();
        foreach ($pings as $p) {
            $arr[] = new xmlrpcval($p->source, 'string');
        }
        $ret = new xmlrpcresp(new xmlrpcval($arr, 'array'));
    }
    return $ret;
}

/* ********************************************************
Section: Blogger 1.0 API Support
This section implements the Blogger 1.0 API for posting to your blog via XML-RPC.

Topic: Overview

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

Topic: Using the API with LnBlog

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

Topic: Configuration

When configuring your blogging client to use Blogger 1.0 with LnBlog, give the
URL of your LnBlog/xmlrpc.php file as the address to handle the requests.  You
can use your normal LnBlog username and password as your login.  For the blog ID,
give the root-relative path to your blog.  If you look on the <index.php> admin
page, this is simply the text that shows up in the drop-down for upgrading your
blog.

When editing posts via the blogger API, the post ID is simply the URL of the
directory in which the post is stored, with the protocol and domain name removed.
So, if your post is at
|http://www.mysite.com/myblog/entries/2006/05/04/03_2100/
then the post ID would be
|myblog/entries/2006/05/04/03_2100/
Note that, for blogs that are hosted on a subdomain, the subdomain leads the ID.
So if your entry is located in a blog at
|http://myblog.mysite.com/entries/2006/05/04/03_2100/
then your post ID will be exactly the same as above.

************************************************** */

# Method: blogger.newPost
# Adds a new post to the blog.
#
# Parameters:
# appkey(string)   - No longer used.  Pass a dumby value.
# blogid(string)   - Identifier for the blog.
# username(string) - Username to log in as.
# password(string) - The password to log in with.
# content(string)  - The body text of the post.
# publish(boolean) - Whether or not to immediately publish the entry.
#                    If set to false, the entry will be saved as a draft.
#
# Returns:
# A string representation of the unique ID of this post.

function blogger_newPost($params) {

    global $xmlrpcerruser;

    $appkey = $params->getParam(0);  # We ignore the appkey.
    $blogid = $params->getParam(1);
    $username = $params->getParam(2);
    $password = $params->getParam(3);
    $content = $params->getParam(4);
    $publish = $params->getParam(5);

    $blog = NewBlog($blogid->scalarval());

    $uid = trim($username->scalarval());
    $pwd = trim($password->scalarval());
    $usr = NewUser($uid);
    $fs = NewFS();
    $publisher = new Publisher($blog, $usr, $fs, new WrapperGenerator($fs), new TaskManager());

    $check = System::instance()->canAddTo($blog, $usr);
    if (true !== $error = authenticate_user($usr, $pwd, $check)) {
        return $error;
    }

    $ent = NewBlogEntry();
    $ent->has_html = MARKUP_HTML;
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

    try {
        if ($publish) {
            $publisher->publishEntry($ent);
        } else {
            $publisher->createDraft($ent);
        }
        return new xmlrpcresp(new xmlrpcval($ent->globalID()));
    } catch (Exception $e) {
        return new xmlrpcresp(0, $xmlrpcerruser+2, "Entry add failed");
    }

}

# Method: blogger.editPost
# Edit an existing post.
#
# Parameters:
# appkey(string)   - No longer used.  Pass a dumby value.
# postid(string)   - Identifier for the post.
# username(string) - Username to log in as.
# password(string) - The password to log in with.
# content(string)  - The new body text of the post.
# publish(boolean) - Whether or not to immediately publish the entry.
#                    The parameter is not currently used by LnBlog.
#
# Returns:
# True on success.  On failure, a fault is raised.

function blogger_editPost($params) {
    global $xmlrpcerruser;

    $appkey = $params->getParam(0);  # We ignore the appkey.
    $postid = $params->getParam(1);
    $username = $params->getParam(2);
    $password = $params->getParam(3);
    $content = $params->getParam(4);
    $publish = $params->getParam(5);

    $ent = NewEntry($postid->scalarval());
    $blog = $ent->getParent();

    $uid = trim($username->scalarval());
    $pwd = trim($password->scalarval());
    $usr = NewUser($uid);
    $fs = NewFS();
    $publisher = new Publisher($blog, $usr, $fs, new WrapperGenerator($fs), new TaskManager());

    $check = System::instance()->canModify($ent, $usr);
    if (true !== $error = authenticate_user($usr, $pwd, $check)) {
        return $error;
    }

    $data = $content->scalarval();
    if (!$data) {
        return new xmlrpcresp(0, $xmlrpcerruser+4, "No data in message - cannot edit post");
    }

    # Test for initial lines to set the subject and/or tags.
    $data = explode("\n", $data);

    if (isset($data[0]) && preg_match("/^Subject:.+/i", trim($data[0]))) {
        $ent->subject = trim(preg_replace("/^Subject:(.+)/i", "$1", trim($data[0])));
        $data[0] = '';
    }
    if (isset($data[1]) && preg_match("/^Tags:.+/i", trim($data[1]))) {
        $ent->tags = trim(preg_replace("/^Tags:(.+)/i", "$1", trim($data[1])));
        $data[1] = '';
    }

    $data = implode("\n", $data);

    $ent->data = $data;

    try {
        if ($ent->isDraft() && $publish) {
            $publisher->publishEntry($ent);
        } else {
            $publisher->update($ent);
        }
        return new xmlrpcresp(new xmlrpcval(true, 'boolean'));
    } catch (Exception $e) {
        return new xmlrpcresp(0, $xmlrpcerruser+2, "Entry update failed");
    }

}

# Method: blogger.deletePost
# Deletes the specified post.
#
# Parameters:
# appkey(string)   - No longer used.  Pass a dumby value.
# postid(string)   - Identifier for the post.
# username(string) - Username to log in as.
# password(string) - The password to log in with.
# publish(boolean) - Whether or not to immediately publish the change.
#                    The parameter is not currently used by LnBlog.

function blogger_deletePost($params) {
    global $xmlrpcerruser;

    $appkey = $params->getParam(0);  # We ignore the appkey.
    $postid = $params->getParam(1);
    $username = $params->getParam(2);
    $password = $params->getParam(3);
    $publish = $params->getParam(4);  # The publish flag is also ignored.

    $ent = NewEntry($postid->scalarval());
    $blog = $ent->getParent();

    $uid = trim($username->scalarval());
    $pwd = trim($password->scalarval());
    $usr = NewUser($uid);
    $fs = NewFS();
    $publisher = new Publisher($blog, $usr, $fs, new WrapperGenerator($fs), new TaskManager());

    $check = System::instance()->canDelete($ent, $usr);
    if (true !== $error = authenticate_user($usr, $pwd, $check)) {
        return $error;
    }

    try {
        $publisher->delete($ent);
        return new xmlrpcresp(new xmlrpcval(true, 'boolean'));
    } catch (Exception $e) {
        return new xmlrpcresp(0, $xmlrpcerruser+2, "Entry update failed");
    }
}

# Method: blogger.getUsersBlogs
# Gets a list of all blogs that a given user can add posts to.
#
# Parameters:
# appkey(string)   - No longer used.  Pass a dumby value.
# username(string) - Username to log in as.
# password(string) - The password to log in with.
#
# Returns:
# An array of structs containing the blog name, id, and URL.

function blogger_getUsersBlogs($params) {
    global $xmlrpcerruser;

    $appkey = $params->getParam(0);  # We ignore the appkey.
    $username = $params->getParam(1);
    $password = $params->getParam(2);

    $uid = trim($username->scalarval());
    $pwd = trim($password->scalarval());
    $usr = NewUser($uid);

    if (true !== $error = authenticate_user($usr, $pwd, true)) {
        return $error;
    }

    $blogs = System::instance()->getUserBlogs($usr);
    if (! empty($blogs)) {
        $resp_arr = array();
        foreach ($blogs as $blg) {
            $resp_arr[] = new xmlrpcval(
                array("url"=> new xmlrpcval($blg->getURL()),
                                "blogName"=>new xmlrpcval($blg->name),
                                "blogid"=>new xmlrpcval($blg->blogid)),
                "struct"
            );
        }
        $ret = new xmlrpcresp(new xmlrpcval($resp_arr, "array"));
    } else {
        $ret = new xmlrpcresp(0, $xmlrpcerruser+4, "This user has no blogs");
    }
    return $ret;
}

# Method: blogger.getUserInfo
# Gets the information for the given user.
#
# Parameters:
# appkey(string)   - No longer used.  Pass a dumby value.
# username(string) - Username to log in as.
# password(string) - The password to log in with.
#
# Returns:
# A struct containing user's userid, firstname, lastname, nickname, email, and
# url.  Note that not all of these necessarily apply to LnBlog, and so any field
# that is not found will be "faked" with a reasonable value or empty.

function blogger_getUserInfo($params) {
    global $xmlrpcerruser;

    $appkey = $params->getParam(0);  # We ignore the appkey.
    $username = $params->getParam(1);
    $password = $params->getParam(2);

    $uid = trim($username->scalarval());
    $pwd = trim($password->scalarval());
    $usr = NewUser($uid);

    if (true !== $error = authenticate_user($usr, $pwd, true)) {
        return $error;
    }

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
        'struct'
    );
    return new xmlrpcresp($user_arr);
}

# Method: blogger.getTemplate
# Gets the template used for the main or entry page.
# This doesn't apply to LnBlog and so always returns a "not implemented" message.
#
# Parameters:
# appkey(string)        - No longer used.  Pass a dumby value.
# blogid(string)        - Identifier for the blog.
# username(string)      - Username to log in as.
# password(string)      - The password to log in with.
# templateType(string)  - The type of template to get.

function blogger_getTemplate($params) {
    global $xmlrpcerruser;
    return new xmlrpcresp(
        0, $xmlrpcerruser+1,
        "Method blogger.getTemplate not implemented."
    );
}

# Method: blogger.setTemplate
# Sets the template used for the main or entry page.
# This doesn't apply to LnBlog and so always returns a "not implemented" message.
#
# Parameters:
# appkey(string)        - No longer used.  Pass a dumby value.
# blogid(string)        - Identifier for the blog.
# username(string)      - Username to log in as.
# password(string)      - The password to log in with.
# templateType(string)  - The type of template to get.

function blogger_setTemplate($params) {
    global $xmlrpcerruser;
    return new xmlrpcresp(
        0, $xmlrpcerruser+1,
        "Method blogger.setTemplate not implemented."
    );
}

/*
Section: MetaWeblog API Support

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
metaWeblog.getPost         - Returns a representation of an existing post.
metaWeblog.newMediaObject - Creates a new image, video file, etc. for the blog.
metaWeblog.getCategories  - Returns the categories known to the blog.
metaWeblog.getRecentPosts - Returns a list of the most recently made posts.

Topic: Configuration

The configuration for the MetaWeblog API is exactly the same as for the Blogger API.
You should use the same format for entry IDs and blog IDs as well as the same end-point
URL.  The only difference is in the commands sent to the server.

Topic: API Extensions

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

function metaWeblog_newPost($params) {

    global $xmlrpcerruser;

    $blogid = $params->getParam(0);
    $username = $params->getParam(1);
    $password = $params->getParam(2);
    $content = $params->getParam(3);
    $publish = $params->getParam(4);  # The publish flag is ignored.

    $blog = NewBlog($blogid->scalarval());

    $uid = trim($username->scalarval());
    $pwd = trim($password->scalarval());
    $usr = NewUser($uid);
    $fs = NewFS();
    $publisher = new Publisher($blog, $usr, $fs, new WrapperGenerator($fs), new TaskManager());

    $check = System::instance()->canAddTo($blog, $usr);
    if (true !== $error = authenticate_user($usr, $pwd, $check)) {
        return $error;
    }

    $ent = NewBlogEntry();
    $ent->has_html = MARKUP_HTML;
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
            case 'categories':
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

    try {
        if ($publish) {
            $publisher->publishEntry($ent);
        } else {
            $publisher->createDraft($ent);
        }
        return new xmlrpcresp(new xmlrpcval($ent->globalID()));
    } catch (Exception $e) {
        return new xmlrpcresp(0, $xmlrpcerruser+2, "Entry add failed");
    }
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

function metaWeblog_editPost($params) {

    global $xmlrpcerruser;

    $postid = $params->getParam(0);
    $username = $params->getParam(1);
    $password = $params->getParam(2);
    $content = $params->getParam(3);
    $publish = $params->getParam(4);  # The publish flag is ignored.

    $uid = $username->scalarval();
    $pwd = $password->scalarval();
    $usr = NewUser($uid);
    $fs = NewFS();

    $postpath = $postid->scalarval();
    $ent = NewBlogEntry($postpath);
    $blog = $ent->getParent();

    $publisher = new Publisher($blog, $usr, $fs, new WrapperGenerator($fs), new TaskManager());

    $check = System::instance()->canModify($ent, $usr);
    if (true !== $error = authenticate_user($usr, $pwd, $check)) {
        return $error;
    }

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
            case 'categories':
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

    try {
        if ($ent->isDraft() && $publish) {
            $publisher->publishEntry($ent);
        } else {
            $publisher->update($ent);
        }

        return new xmlrpcresp(new xmlrpcval(true, 'boolean'));
    } catch (Exception $e) {
        return new xmlrpcresp(0, $xmlrpcerruser+2, "Entry edit failed");
    }
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

function metaWeblog_getPost($params) {
    global $xmlrpcerruser;

    $postid = $params->getParam(0);
    $username = $params->getParam(1);
    $password = $params->getParam(2);

    $uid = $username->scalarval();
    $pwd = $password->scalarval();
    $usr = NewUser($uid);

    $postpath = $postid->scalarval();
    $ret = false;

    # I think we can safely skip the permissions check here, since all the
    # information is public anyway.  We'll just check for authentication
    # instead.
    if (true !== $error = authenticate_user($usr, $pwd, true)) {
        return $error;
    }

    $ent = NewBlogEntry($postpath);

    if ($ent->isEntry()) {
        $ret = new xmlrpcresp(entry_to_struct($ent));
    } else {
        $ret = new xmlrpcresp(0, $xmlrpcerruser+2, "Entry does not exist");
    }
    return $ret;
}

# Method: metaWeblog.newMediaObject
# Uploads a file to the weblog over XML-RPC.
# New media objects are passed as structs, with 'name', 'type', and 'bits'
# fields.  The 'bits' field is the base64-encoded data for the file.
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

function metaWeblog_newMediaObject($params) {
    global $xmlrpcerruser;

    $fs = NewFS();

    $blogid = $params->getParam(0);
    $username = $params->getParam(1);
    $password = $params->getParam(2);
    $data = $params->getParam(3);

    $uid = $username->scalarval();
    $pwd = $password->scalarval();
    $usr = NewUser($uid);
    $blog = NewBlog($blogid->scalarval());

    $check = System::instance()->canModify($blog, $usr);
    if (true !== $error = authenticate_user($usr, $pwd, true)) {
        return $error;
    }

    $name = $data->structmem('name');
    $type = $data->structmem('type');
    $bits = $data->structmem('bits');
    @$ent = $data->structmem('entryid');

    if (! empty($ent)) {
        $postpath = $ent->scalarval();
        $entry = NewEntry($postpath);
        if ($entry->isEntry() && System::instance()->canModify($entry, $usr)) {
            $path = Path::mk($entry->localpath(), $name->scalarval());
        } else {
            return new xmlrpcresp(0, $xmlrpcerruser+3, "Invalid login - cannot add files to this entry");
        }
    } else {
        $path = Path::mk($blog->home_path, $name->scalarval());
    }
    $ret = $fs->write_file($path, base64_decode($bits->scalarval()));

    if ($ret) {
        $resolver = new UrlResolver();
        $url = new xmlrpcval($resolver->localpathToUri($path, $blog), 'string');
        $ret = new xmlrpcresp(new xmlrpcval(array('url'=>$url), 'struct'));
    } else {
        $ret = new xmlrpcresp(0, $xmlrpcerruser+4, "Cannot create file $name");
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
# contain description, htmlUrl, and rssUrl elements.  Note that LnBlog supplies
# RSS as an optional plugin, so the RSS URL may be empty.
function metaWeblog_getCategories($params) {
    global $xmlrpcerruser;

    $blogid = $params->getParam(0);
    $username = $params->getParam(1);
    $password = $params->getParam(2);

    $uid = $username->scalarval();
    $pwd = $password->scalarval();
    $usr = NewUser($uid);

    # Again, let's just skip the permissions check since this information is
    # public anyway.
    if (true !== $error = authenticate_user($usr, $pwd, true)) {
        return $error;
    }

    $blog = NewBlog($blogid->scalarval());

    if ($blog->isBlog()) {
        $arr = array();
        $base_feed_path = Path::mk($blog->home_path, BLOG_FEED_PATH);
        $base_feed_uri = $blog->uri('base').BLOG_FEED_PATH.'/';
        foreach ($blog->tag_list as $tag) {
            $cat = array();
            $cat['description'] = new xmlrpcval(htmlspecialchars($tag), 'string');
            $cat['categoryName'] = new xmlrpcval(htmlspecialchars($tag), 'string');
            $cat['categoryId'] = new xmlrpcval(htmlspecialchars($tag), 'string');
            $cat['htmlUrl'] = new xmlrpcval($blog->uri('tags', ['tag' => urlencode($tag)]), 'string');

            $topic = preg_replace('/\W/', '', $tag);
            $rss_feeds = $blog->raiseEventAndPassthruReturn(Blog::RSS_FEED_EVENT, [$topic]);
            $rss_url = $rss_feeds[0] ?? '';

            $cat['rssUrl'] = new xmlrpcval($rss_url, 'string');
            $arr[$tag] = new xmlrpcval($cat, 'struct');
        }
        $ret = new xmlrpcresp(new xmlrpcval($arr, 'struct'));
    } else {
        $ret = new xmlrpcresp(0, $xmlrpcerruser+2, "Blog does not exist");
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

function metaWeblog_getRecentPosts($params) {
    global $xmlrpcerruser;

    $blogid = $params->getParam(0);
    $username = $params->getParam(1);
    $password = $params->getParam(2);
    $numposts = $params->getParam(3);

    $uid = $username->scalarval();
    $pwd = $password->scalarval();
    $usr = NewUser($uid);

    # Again, let's just skip the permissions check since this information is
    # public anyway.
    if (true !== $error = authenticate_user($usr, $pwd, true)) {
        return $error;
    }

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
    return $ret;
}

# Section: MetaWeblog Blogger compatibility.
# The following MetaWeblog methods mirror the Blogger methods of the
# corresponding name.  These methods were suggested by Dave Winer
# in this post <http://www.xmlrpc.com/stories/storyReader$2460>.
# However, for whatever reason, the MetaWeblog API spec was never
# ammended to include these.  Does that mean they don't officially
# exist?  I don't know.

# Method: metaWeblog.deletePost
# A MetaWeblog alias of blogger.deletePost.

# Method: metaWeblog.getTemplate
# A MetaWeblog alias of blogger.getTemplate.

# Method: metaWeblog.setTemplate
# A MetaWeblog alias of blogger.setTemplate.

# Method: metaWeblog.getUsersBlogs
# A MetaWeblog alias of blogger.getUsersBlogs.

################################################################################
# Section: MoveableType API
# The following methods are from the MovableType XML-RPC API.
# The reference is available at <http://www.movabletype.org/mt-static/docs/mtmanual_programmatic.html>.

# Method: mt.getRecentPostTitles
# Gets a stripped-down version of the recent posts list.

function mt_getRecentPostTitles($params) {
    global $xmlrpcerruser;

    $blogid = $params->getParam(0);
    $username = $params->getParam(1);
    $password = $params->getParam(2);
    $numposts = $params->getParam(3);

    $uid = $username->scalarval();
    $pwd = $password->scalarval();
    $usr = NewUser($uid);

    # Again, let's just skip the permissions check since this information is
    # public anyway.
    if (true !== $error = authenticate_user($usr, $pwd, true)) {
        return $error;
    }

    $blog = NewBlog($blogid->scalarval());

    if ($blog->isBlog()) {
        $entries = $blog->getRecent($numposts->scalarval());
        $arr = array();
        foreach ($entries as $ent) {
            $post = array();
            $post['dateCreated'] = new xmlrpcval(fmtdate(ENTRY_DATE_FORMAT, $ent->post_ts), 'dateTime.iso8601');
            $post['userid'] = new xmlrpcval($ent->uid, 'string');
            $post['postid'] = new xmlrpcval($ent->globalID(), 'string');
            $post['title'] = new xmlrpcval($ent->subject, 'string');
            $arr[] = new xmlrpcval($post, 'struct');
        }
        $ret = new xmlrpcresp(new xmlrpcval($arr, 'array'));
    } else {
        $ret = new xmlrpcresp(0, $xmlrpcerruser+2, "Blog does not exist");
    }
    return $ret;
}

# Method: mt.getCategoryList
# Gets a list of the category IDs and names for this blog.

function mt_getCategoryList($params) {
    global $xmlrpcerruser;

    $blogid = $params->getParam(0);
    $username = $params->getParam(1);
    $password = $params->getParam(2);

    $uid = $username->scalarval();
    $pwd = $password->scalarval();
    $usr = NewUser($uid);

    # Again, let's just skip the permissions check since this information is
    # public anyway.
    if (true !== $error = authenticate_user($usr, $pwd, true)) {
        return $error;
    }

    $blog = NewBlog($blogid->scalarval());

    if ($blog->isBlog()) {
        $arr = array();
        foreach ($blog->tag_list as $tag) {
            $cat = array();
            $cat['categoryId'] = new xmlrpcval($tag, 'string');
            $cat['categoryName'] = new xmlrpcval($tag, 'string');
            $arr[] = new xmlrpcval($cat, 'struct');
        }
        $ret = new xmlrpcresp(new xmlrpcval($arr, 'array'));
    } else {
        $ret = new xmlrpcresp(0, $xmlrpcerruser+2, "Blog does not exist");
    }
    return $ret;
}

# Method: mt.getPostCategories
# Gets a list of the category IDs and names for this blog.

function mt_getPostCategories($params) {
    global $xmlrpcerruser;

    $postid = $params->getParam(0);
    $username = $params->getParam(1);
    $password = $params->getParam(2);

    $uid = $username->scalarval();
    $pwd = $password->scalarval();
    $usr = NewUser($uid);

    # Again, let's just skip the permissions check since this information is
    # public anyway.
    if (true !== $error = authenticate_user($usr, $pwd, true)) {
        return $error;
    }

    $ent = NewBlogEntry($postid->scalarval());

    if ($ent->isEntry()) {
        $arr = array();
        foreach ($ent->tags() as $tag) {
            $cat = array();
            $cat['categoryID'] = new xmlrpcval($tag, 'string');
            $cat['categoryName'] = new xmlrpcval($tag, 'string');
            $cat['isPrimary'] = new xmlrpcval(false, 'boolean');
            $arr[$tag] = new xmlrpcval($cat, 'struct');
        }
        $ret = new xmlrpcresp(new xmlrpcval($arr, 'struct'));
    } else {
        $ret = new xmlrpcresp(0, $xmlrpcerruser+2, "Post does not exist");
    }
    return $ret;
}

# Method: mt.setPostCategories
# Gets a list of the category IDs and names for this blog.

function mt_setPostCategories($params) {
    global $xmlrpcerruser;

    $postid = $params->getParam(0);
    $username = $params->getParam(1);
    $password = $params->getParam(2);
    $cats = $params->getParam(3);

    $uid = $username->scalarval();
    $pwd = $password->scalarval();
    $usr = NewUser($uid);

    # Again, let's just skip the permissions check since this information is
    # public anyway.
    if (true !== $error = authenticate_user($usr, $pwd, true)) {
        return $error;
    }

    $ent = NewBlogEntry($postid->scalarval());

    if ($ent->isEntry()) {
        $tags = array();
        for ($i=0; $i < $cats->arraysize(); $i++) {
            $mem = $cats->arraymem($i);
            $cat = $mem->structmem('categoryId');
            $tags[] = $cat->scalarval();
        }
        $ent->tags($tags);
        $ret = $ent->update();
        $ret = new xmlrpcresp(new xmlrpcval($ret, 'boolean'));
    } else {
        $ret = new xmlrpcresp(0, $xmlrpcerruser+2, "Post does not exist");
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

    $arr['categories'] = new xmlrpcval($cat_list, 'array');

    $arr['guid'] = new xmlrpcval($ent->globalID(), 'string');
    $arr['pubDate'] = new xmlrpcval(date('r', $ent->post_ts), 'string');

    $arr['postid'] = new xmlrpcval($ent->globalID(), 'string');
    $arr['userid'] = new xmlrpcval($ent->uid, 'string');
    $arr['dateCreated'] = new xmlrpcval(iso8601_encode($ent->post_ts), 'dateTime.iso8601');
    $arr['permaLink'] = new xmlrpcval($ent->permalink(), 'string');

    # MoveableType extensions
    $arr['mt_excerpt'] = new xmlrpcval('', 'string');
    $arr['mt_text_more'] = new xmlrpcval('', 'string');
    $arr['mt_excerpt'] = new xmlrpcval('', 'string');
    $arr['mt_keywords'] = new xmlrpcval('', 'string');
    $arr['mt_allow_comments'] = new xmlrpcval($ent->allow_comment ? 1 : 0, 'int');
    $arr['mt_allow_pings'] = new xmlrpcval($ent->allow_tb ? 1 : 0, 'int');
    $arr['mt_convert_breaks'] = new xmlrpcval($ent->has_html != MARKUP_HTML ? 1 : 0, 'int');

    return new xmlrpcval($arr, 'struct');
}

# Method: mt.supportedMethods
# A zero-parameter call that gets a list of the methods the server supports.
#
# Returns:
# An array of all supported method names.
function mt_supportedMethods() {
    global $function_map;
    $ret = array();
    foreach ($function_map as $method=>$map) {
        $ret[] = new xmlrpcval($method, 'string');
    }
    return new xmlrpcresp(new xmlrpcval($ret, 'array'));
}

# Method: mt.supportedTextFilters
# Gets a list text filtering plugins supported by the server.  As LnBlog does
# not currently support this concept, this just returns an empty array.
#
# Returns:
# An array of structs, with a key and a label field.
function mt_supportedTextFilters() {
    return new xmlrpcresp(new xmlrpcval(array(), 'array'));
}

# Method: mt.getTrackbackPings
# Gets the Trackback pings for a particular entry.
#
# Parameters:
# postid - The identifier for this post.
#
# Returns:
# An array of structs, each with a pingTitle, pingURL, and pingIP.
function mt_getTrackbackPings($params) {
    $entid = $params->getParam(0);
    $ent = NewEntry($entid->scalarval());

    if (! $ent->isEntry() ) {
        return new xmlrpcresp(0, 33, "Entry ID not recognized.");
    }

    $trackbacks = $ent->getTrackbackArray();
    $ret = array();

    foreach ($trackbacks as $tb) {
        $s = array();
        $s['pingTitle'] = new xmlrpcval($tb->title, 'string');
        $s['pingURL'] = new xmlrpcval($tb->url, 'string');
        $s['pingIP'] = new xmlrpcval($tb->ip, 'string');
        $ret[] = new xmlrpcval($s, 'struct');
    }
    return new xmlrpcresp(new xmlrpcval($ret, 'array'));
}

# Method: mt.publishPost
# Rebuilds all static files for a particular post.
# Since LnBlog is currently all-dynamic and does not yet have unpublished
# posts, this method always returns true.
#
# Parameters:
# postid   - The ID of the post to edit.
# username - The username with which to log in.
# password - The associated password.
#
# Returns:
# True on success, a fault on failure.
function mt_publishPost($params) {
    return new xmlrpcresp(new xmlrpcval(true, 'boolean'));
}

function authenticate_user($usr, $pwd, $check) {
    global $xmlrpcerruser;
    try {
        if ( !$usr->authenticateCredentials($pwd) || !$check) {
            return new xmlrpcresp(0, $xmlrpcerruser+3, "Error authenticating credentials");
        }
    } catch (UserLockedOut $locked_out) {
        return new xmlrpcresp(0, $xmlrpcerruser+5, "Invalid login - user account is currently locked");
    } catch (UserAccountLocked $locked) {
        $template = NewTemplate("user_lockout_tpl.php");
        $template->set('USER', $usr);
        $template->set('MODE', 'email');
        $notifier = new Notifier();
        $notifier->sendEmail(
            $usr->email(),
            _("LnBlog account locked"),
            $template->process(),
            "From: LnBlog notifier <".EMAIL_FROM_ADDRESS.">"
        );
        return new xmlrpcresp(0, $xmlrpcerruser+6, "Invalid login - too many attempts, account locked");
    }
    return true;
}

$server = new xmlrpc_server($function_map);


