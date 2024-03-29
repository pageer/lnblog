<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005-2011 Peter A. Geer <pageer@skepticats.com>

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

use LnBlog\Model\Reply;

/*
Class: BlogComment
Represents a comment on a blog entry or article.

Inherits:
<LnBlogObject>, <Entry>

Events:
OnInit         - Fired when object is first created.
InitComplete   - Fired at end of constructor.
OnInsert       - Fired before object is saved to persistent storage.  This is
                 run *after* the insertion setup is done, but *before*
                 anything is actually saved to disk.
InsertComplete - Fired after object has finished saving.
OnDelete       - Fired before object is deleted.
DeleteComplete - Fired after object is deleted.
OnUpdate       - Fired before changes are saved to persistent storage.
UpdateComplete - Fired after changes to object are saved.
OnOutput       - Fired before output is generated.
OutputComplete - Fired after output has finished being generated.
POSTRetrieved  - Fired after data has been retreived from an HTTP POST.
*/
class BlogComment extends Entry implements Reply
{

    public $postid;
    public $name;
    public $email;
    public $url;
    public $show_email;

    public $parent;
    public $control_bar= [];

    public function __construct ($path = "", $filesystem = null, UrlResolver $resolver = null) {
        parent::__construct($filesystem ?: NewFS(), $resolver);
        $this->raiseEvent("OnInit");
        $this->ip = get_ip();
        $this->timestamp = 0;
        $this->subject = "";
        $this->data = "";
        $this->file = $path ;
        $this->url = "";
        $this->email = "";
        $this->name = ANON_POST_NAME;
        $this->has_html = MARKUP_NONE;
        $this->show_email = COMMENT_EMAIL_VIEW_PUBLIC;
        $this->exclude_fields = array('exclude_fields', 'metadata_fields',
                                      'file', 'fs', 'url_resolver', 'parent', 'control_bar');
        $this->metadata_fields = array(
            "id"=>"postid",
            "uid"=>"userid",
            "name"=>"name",
            "email"=>"e-mail",
            "url"=>"url",
            "show_email"=>"show_email",
            "timestamp"=>"timestamp",
            "post_ts"=>"posttimestamp",
            "ip"=>"ip",
            "subject"=>"subject"
        );

        if ( file_exists($this->file) ) {
            $this->readFileData();
        } elseif ( file_exists($this->getFilename($path)) ) {
            $this->file = $this->getFilename($path);
            $this->readFileData();
        } else {
            $this->file = "";
        }
        # Over-ride the personal info for comments by logged-in users.
        if ($this->uid) {
            $usr = NewUser($this->uid);
            $this->name = $usr->name();
            $this->email = $usr->email();
            $this->url = $usr->homepage();
        }
        $this->raiseEvent("InitComplete");
    }

    /*
    Method: getPath
    Get the path to use for to store the comment.  This is specific to
    file-based storage and so is for *internal use only*.

    Parameters:
    ts - The timestamp of the entry.

    Returns:
    A string to use for the file name.
    */
    public function getPath($ts) {
        $base = date(COMMENT_PATH_FORMAT, $ts);
        return $base;
    }

    /*
    Method: update
    Commit changes to a comment.

    Returns:
    True on success, false on failure.
    */
    public function update() {
        $this->raiseEvent("OnUpdate");
        $base_path = dirname($this->file);
        $ret = $this->insert($base_path);
        $this->raiseEvent("UpdateComplete");
        return $ret;
    }

    /*
    Method: delete
    Delete a comment.

    Returns:
    True on success, false on failure.
    */
    public function delete() {
        $this->raiseEvent("OnDelete");
        $fs = NewFS();
        $ret = $fs->delete($this->file);
        $this->raiseEvent("DeleteComplete");
        return $ret;
    }

    /*
    Method: insert
    Add a new comment on an entry or article.

    Parameters:
    Entry - The entry to which this comment will belong.  This determines where
            the comment is stored.

    Returns:
    True on success, false on failure.
    */
    public function insert($entry, DateTime $datetime = null) {

        $curr_ts = $datetime ? $datetime->getTimestamp() : time();
        $usr = NewUser();
        if (!$this->uid) $this->uid = $usr->username();

        $basepath = Path::mk($entry->localpath(), ENTRY_COMMENT_DIR);

        $this->file = Path::mk($basepath, $this->getPath($curr_ts).COMMENT_PATH_SUFFIX);
        $this->timestamp = $curr_ts;
        $this->ip = get_ip();

        # Initial setup complete, start writing things to disk.
        $this->raiseEvent("OnInsert");
        # If there is no data for this comment, then abort.
        if (! $this->data) return false;
        if (! is_dir($basepath) ) {
            $wrappers = new WrapperGenerator($this->fs);
            $ret = $wrappers->createDirectoryWrappers($basepath, WrapperGenerator::ENTRY_COMMENTS);
            if (! $ret) return false;
        }

        $ret = $this->writeFileData();
        $this->raiseEvent("InsertComplete");
        return $ret;
    }

    /*
    Method: getPostData
    Pulls data out of the HTTP POST headers and into the object.

    The interface for this uses pre-defined POST field names they are as
    follows.  If the poster is an authenticated user, then the userid is also
    recorded automatically from the HTTP session.
    username  - The name of the poster.
    email     - The poster's e-mail address.
    url       - The poster's homepage.
    showemail - If not empty, show the poster's e-mail address publically.
    subject   - The subject of the post.
    data      - The post content.  This cannot be empty.
    */
    public function getPostData($form = null) {
        if (! has_post()) return false;
        $this->name = POST("username");
        $this->email = POST("email");
        $this->url = POST("homepage");
        $this->subject = POST("subject");
        $this->data = POST("data");
        $this->show_email = POST("showemail") ? true : false;

        $processor = TextProcessor::get(MARKUP_NONE, $this);
        $processor->no_surround = true;

        foreach ($this->custom_fields as $fld=>$desc) {
            $this->$fld = POST($fld);
            $processor->setText($this->$fld);
            $this->$fld = $processor->getHTML($this->$fld);
        }
        # Note: Don't strip HTML from the comment data, because we do that
        # when we add in the links and other markup.
        $this->name = $processor->getHTML($this->name);
        $this->email = $processor->getHTML($this->email);
        $this->subject = $processor->getHTML($this->subject);
        # Don't escape the URL, because we want the actual value.
        $this->url = $this->url;

        if (! $this->uid) {
            $u = NewUser();
            $this->uid = $u->username();
        }

        $this->raiseEvent("POSTRetreived");
    }

    /*
    Method: getAnchor
    Get text to use as the name attribute in an HTML anchor tag.

    Returns:
    A string for anchor text based on the file name/storage ID.
    */
    public function getAnchor() {
        $ret = basename($this->file);
        $ret = preg_replace("/.\w\w\w$/", "", $ret);
        $ret = "comment".$ret;
        return $ret;
    }

    /*
    Method: getFilename
    The inverse of <getAnchor>, this converts an anchor into a file.

    Parameters:
    anchor - An anchor string generated by <getAnchor>.

    Returns:
    A string with the name of the associated file.
    */
    public function getFilename($anchor) {
        if (strpos($anchor, "#") !== false) {
            $pieces = explode('#', $anchor);
            $entid = dirname($pieces[0]);
            $cmtid = $pieces[1];
        } else {
            $entid = false;
            $cmtid = $anchor;
        }
        $ent = NewEntry($entid);
        $ret = substr($cmtid, 7);
        $ret .= COMMENT_PATH_SUFFIX;
        $ret = Path::mk($ent->localpath(), ENTRY_COMMENT_DIR, $ret);
        $ret = realpath($ret);
        return $ret;
    }

    # Method: globalID
    # Get the global identifier for this comment.
    public function globalID() {
        $parent = $this->getParent();
        $id = $parent->globalID() . '/' . ENTRY_COMMENT_DIR . '/#' . $this->getAnchor();
        return $id;
    }

    /*
    Method: permalink
    Get the permalink to the object.  This is essentially the URI of the
    parent's comments page with the anchor name appended.

    Returns:
    The full URI to the object's permalink, including page anchor.
    */
    public function permalink() {
        return $this->uri("permalink");
    }

    /*
    Method: isComment
    Determines whether or not the object is an existing, saved comment.

    Parameters:
    path - The *optional* path to the comment data file.  If not specified,
           then the file property is used.

    Returns:
    True if the comment data file exists, false otherwise.
    */
    public function isComment($path=false) {
        if (!$path) $path = $this->file;
        return file_exists($path);
    }

    /*
    Method: getParent
    Gets a copy of the parent object of this comment, i.e. the object which
    this is a comment on.

    Returns:
    A BlogEntry.
    */
    public function getParent() {
        if ($this->parent) {
            return $this->parent;
        }
        if (file_exists($this->file)) {
            $ret = NewEntry(dirname(dirname($this->file)));
        } else {
            $ret = NewEntry();
        }
        return $ret;
    }

    /*
    Method: get
    Gets the markup to display the object in a web browser.

    Parameters:
    show_edit_controls - *Optional* boolean that determines whether or not
                         to display edit controls, e.g. delete link.

    Returns:
    A string containing the markup.
    */
    public function get($show_edit_controls=false) {

        # An array of the form label=>URL which holds the list of
        # administrative items, such as the delete link.
        $this->control_bar = array();
        # Add the delete link.
        #$this->control_bar[] = '<a href="'.$this->uri("delete").
        #   '" onclick="return comm_del(this,\''.$this->getAnchor()
        #   .'\');">'._("Delete").'</a>';

        $this->control_bar[] =
            ahref(
                $this->uri("delete"), _("Delete"),
                array('class'=>'deletelink')
            ); #, 'id'=>$this->globalID()));

        ob_start();
        $this->raiseEvent("OnOutput");
        $ret = ob_get_contents();
        ob_end_clean();

        $t = NewTemplate(COMMENT_TEMPLATE);

        $blog = NewBlog();
        $usr = NewUser();
        $show_edit_controls = System::instance()->canModify($this->getParent());

        if (! $this->name) {
            $this->name = ANON_POST_NAME;
        }
        if (! $this->subject) {
            $this->subject = NO_SUBJECT;
        }
        if ($this->uid) {
            $u = NewUser($this->uid);
            $u->exportVars($t);
        }

        $t->set("SUBJECT", $this->subject);
        if ( strtolower(substr(trim($this->url), 0, 4)) != "http" && $this->url != "" ) {
            $this->url = "http://".$this->url;
        }
        $this->url = filter_var($this->url, FILTER_VALIDATE_URL);

        $t->set("URL", $this->url);
        $t->set("NAME", $this->name);
        $t->set("DATE", $this->prettyDate($this->post_ts));
        $t->set("EDITDATE", $this->prettyDate());
        if ($this->show_email || $usr->checkLogin()) {
            $t->set("SHOW_MAIL", true);
            $this->email = filter_var($this->email, FILTER_VALIDATE_EMAIL);
            $t->set("EMAIL", $this->email);
        } else {
            $t->set("SHOW_MAIL", false);
            $t->set("EMAIL", "");
        }
        $t->set("ANCHOR", $this->getAnchor());
        $t->set("SHOW_CONTROLS", $show_edit_controls);
        $t->set("BODY", $this->markup($this->data, COMMENT_NOFOLLOW));
        $t->set("CONTROL_BAR", $this->control_bar);

        $ret .= $t->process();
        ob_start();
        $this->raiseEvent("OutputComplete");
        $ret .= ob_get_contents();
        ob_end_clean();

        return $ret;
    }
}
