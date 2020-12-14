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

/*
Class: BlogEntry
Represents an entry in the weblog.

Inherits:
<LnBlogObject>, <Entry>

Events:
OnInit         - Fired when object is first created.
InitComplete   - Fired at end of constructor.
OnInsert       - Fired before object is saved to persistent storage.
InsertComplete - Fired after object has finished saving.
OnDelete       - Fired before object is deleted.
DeleteComplete - Fired after object is deleted.
OnUpdate       - Fired before changes are saved to persistent storage.
UpdateComplete - Fired after changes to object are saved.
OnOutput       - Fired before output is generated.
OutputComplete - Fired after output has finished being generated.
POSTRetrieved  - Fired after data has been retrieved from an HTTP POST.
*/

class BlogEntry extends Entry implements AttachmentContainer
{

    const AUTO_PUBLISH_FILE = 'publish.txt';

    public $send_pingback = true;
    public $allow_pingback = true;
    public $allow_comment = true;
    public $allow_tb = true;
    public $has_html = MARKUP_BBCODE;
    public $enclosure = '';
    public $abstract;
    public $is_article = false;
    public $article_path = '';
    public $is_sticky = false;
    public $autopublish = false;
    public $autopublish_date = '';
    public $permalink_name = '';
    
    # Note: This is for test injection
    public $parent = null;

    private $filemanager;

    public function __construct($path = "", $filesystem = null, $file_manager = null, UrlResolver $resolver = null) {
        $fs = $filesystem ?: NewFS();
        parent::__construct($fs, $resolver);

        $this->filemanager = $file_manager ?: new FileManager($this, $fs);

        $this->initVars();
        $this->raiseEvent("OnInit");

        if ($path !== null) {
            $this->getFile(
                $path, ENTRY_DEFAULT_FILE,
                array('entry', 'draft'),
                array(BLOG_ENTRY_PATH, BLOG_DRAFT_PATH),
                array('/^\d{4}\/\d{2}\/\d{2}_\d{4}\d?\d?$/',
                '/^\d{2}_\d{4}\d?\d?$/')
            );

            if ( $this->fs->file_exists($this->file) ) {
                $this->readFileData();
            }
        }

        $this->raiseEvent("InitComplete");
    }

    # Initializes the member variables.
    # This is for INTERNAL USE ONLY and exists mainly to pass on the
    # variables to subclasses without having to call the entire constructor.
    public function initVars() {
        $this->enclosure = '';
        $this->has_html = MARKUP_BBCODE;
        $this->allow_comment = true;
        $this->allow_tb = true;
        $this->allow_pingback = true;
        $this->send_pingback = true;
        $this->custom_fields = array();
        $this->exclude_fields = array(
            "exclude_fields",
            "metadata_fields",
            "file",
            "fs",
            "url_resolver",
            "parent",
        );
        $this->metadata_fields = array(
            "id"=>"postid",
            "uid"=>"userid",
            "timestamp"=>"timestamp",
            "post_ts"=>"posttimestamp",
            "ip"=>"ip",
            "subject"=>"subject",
            "abstract"=>"abstract",
            "is_article" => "is_article",
            "article_path" => "article_path",
            "autopublish" => "autopublish",
            "autopublish_date" => "autopublish_date",
            "allow_comment"=>"allowcomment",
            "has_html"=>"hashtml",
            "tags"=>"tags",
            "allow_tb"=>"allowtrackback",
            "allow_pingback"=>"allowpingback",
            "enclosure"=>"enclosure",
            "is_sticky"=>"is_sticky",
            "permalink_name" => "permalink_name",
        );
    }

    # Gets the directory and data file for this entry.
    # Again, this is for INTERNAL USE ONLY and is inherited, with parameters,
    # by the Article class.
    public function getFile($path, $revision, $getvar='entry',
                     $subdir=BLOG_ENTRY_PATH,
                     $id_re='/^\d{4}\/\d{2}\/\d{2}_\d{4}\d?\d?$/') {
        $blog = NewBlog();
        $path = trim($path);

        # Account for $getvar being an array.  If it is, just get the first
        # element that's actually set.
        $first_get = '';
        if (is_array($getvar)) {
            foreach ($getvar as $g) {
                if (isset($_GET[$g])) {
                    $first_get = $_GET[$g];
                }
            }
        } else {
            $first_get = GET($getvar);
        }

        $full_path = $this->fs->is_dir($path) ?
            $path :
            Path::mk($blog->home_path, $path);

        # Auto-detect the current entry.  If no path is given,
        # then assume the current directory.
        if ($this->fs->is_dir($full_path)) {

            $this->file = Path::mk($full_path, $revision);
            # Support old blog entry format.
            if (! $this->fs->file_exists($this->file) ) $this->tryOldFileName();

        } elseif ($first_get || $path) {
            # If $path is an identifier, then convert it to a real path.

            $entrypath = trim($path ? $path : sanitize($first_get));

            # If the path is a short entry ID, then try to detect the blog and
            # reconstruct the full path.
            if (is_array($id_re)) {
                $has_match = 0;
                foreach ($id_re as $re) {
                    $has_match = preg_match($re, $entrypath);
                    if ($has_match) break;
                }
            } else $has_match = preg_match($id_re, $entrypath);

            if ( $has_match ) {

                # If we can pass a short ID, it's assumed that we can find the
                # current blog from the environment (query string, config.php, etc.)
                $entrypath = str_replace("/", PATH_DELIM, $entrypath);

                if (is_array($subdir)) {
                    foreach ($subdir as $s) {
                        $f = Path::mk($blog->home_path, $s, $entrypath, $revision);
                        if ($this->fs->file_exists($f)) $this->file = $f;
                    }
                    if (! $this->file) {
                        $this->file = Path::mk($blog->home_path, $subdir[0], $entrypath, $revision);
                    }
                } else $this->file = Path::mk($blog->home_path, $subdir, $entrypath, $revision);


            } else {
                # If we don't have a short entry ID, assume it's a global ID.
                $entrypath = $this->getPathFromGlobalId($entrypath);
                $this->file = Path::mk($entrypath, $revision);
            }

            if (! $this->fs->file_exists($this->file)) {
                $this->tryOldFileName();
            }

        } else {

            $this->file = Path::mk($this->fs->getcwdLocal(), $revision);
            if (! $this->fs->file_exists($this->file) ) $this->tryOldFileName();

            # We might be in a comment or trackback directory,
            if (! $this->isEntry() ) {
                $tmpfile = Path::mk(dirname($this->fs->getcwdLocal()), $revision);
                if (! $this->fs->file_exists($tmpfile) ) {
                    $this->tryOldFileName();
                }
            }
        }

        return $this->file;
    }

    # For INTERNAL USE ONLY.  If the calculated entry file does
    # not exist, try the old filename and change the file
    # property if that does exist.
    public function tryOldFileName() {
        $tmpfile = dirname($this->file);
        $tmpfile = Path::mk($tmpfile, "current.htm");
        if ($this->fs->file_exists($tmpfile)) {
            $this->file = $tmpfile;
        }
    }

    /*
    Method: getParent
    Get a copy of the parent of this objcet, i.e. the blog to which it
    belongs.

    Returns:
    A Blog object.
    */
    public function getParent() {
        if ($this->parent) {
            return $this->parent;
        }
        if ($this->fs->file_exists($this->file)) {
            $dir = $this->file;
            # If this is an article, the blog is 3 levels up.
            for ($i=0; $i<3; $i++) $dir = dirname($dir);
            $ret = NewBlog($dir);
            if (!$ret->isBlog()) {
                # If the entry is a BlogEntry, then the parent will be 2 more
                # (i.e. 5) levels up
                for ($i=0; $i<2; $i++) $dir = dirname($dir);
                $ret = NewBlog($dir);
            }
        } else $ret = NewBlog();
        $this->parent = $ret;
        return $ret;
    }

    # Method: entryID
    # Gets an identifier for the current entry.
    #
    # Returns:
    # For file-based storage, string containing the last part of the path.
    # Normally, this is in the form ##/##/##_#### or ##_#### for drafts

    public function entryID() {
        $temp = dirname($this->file);
        $ret = basename($temp);  # Add day component.
        if ($this->isDraft()) {
            return $ret;
        } elseif ($this->isArticle()) {
            $temp = dirname($temp);
            $ret = basename($temp)."/".$ret;
            return $ret;
        } else {
            $temp = dirname($temp);
            $ret = basename($temp)."/".$ret;  # Add month component.
            $temp = dirname($temp);
            $ret = basename($temp)."/".$ret;  # Add year component.
        }
        return $ret;
    }

    # Method: globalID
    # A unique ID for use with blogging APIs.  Note that this ID embedds the
    # parent blog's ID, whereas the <entryID> method provides an ID *within*
    # the current blog.
    #
    # Returns:
    # A string with the unique ID.

    public function globalID() {
        $blog = $this->getParent();
        $filepath = $this->fs->realpath(dirname($this->file));
        $root = $this->fs->realpath($blog->home_path);
        $ret = substr_replace($filepath, '', 0, strlen($root));
        $ret = str_replace(Path::$sep, '/', $ret);
        if (strpos($ret, '/') !== 0) {
            $ret = '/' . $ret;
        }
        $ret = $blog->blogid . $ret;
        return trim($ret, '/');
    }

    # Method: getEnclosure
    # Gets the URL, file size, and media type of the file set as the enclosure
    # for this entry, it if exists.  The file is checkedy by converting the
    # URL into a local path using the same rules as entry URL absolutizaiton,
    # or simply doing a URL to localpath conversion.
    #
    # Returns:
    # If the enclosure property is set and the file is found, returns an array
    # with elements "url", "length", and "type".  If the file is not found, then
    # checks if the enclosure matches the expected content or an enclosure tag in
    # an RSS feed.  If so, extracts the data into an array.
    # Otherwise Otherwise, returns false.
    public function getEnclosure() {
        # Remove stray whitespace.
        $enc = trim($this->enclosure);

        # Strip any query string.
        if (strpos($enc, '?') !== false) {
            $idx = strpos($enc, '?');
            $enc = substr($enc, 0, $idx);
        }

        if (! $enc) return false;

        # Calculate the enclosure path, using the same rules as LBCode URI
        # absolutizing.
        # No slash = relative to entry directory.
        if (strpos($enc, '/') === false) {
            $path = Path::mk($this->localpath(), $enc);

        # Slash but not at start = relative to blog directory
        } elseif (! strpos($enc, ':') && substr($enc, 1, 1) != '/') {
            $blog = $this->getParent();
            $path = Path::mk($blog->home_path, $enc);
        } else {
            $path = $this->url_resolver->uriToLocalpath($enc, $this->getParent());
        }

        if ($this->fs->file_exists($path)) {
            $ret = array();
            $ret['url'] = $this->url_resolver->localpathToUri($path, $this->getParent());
            $ret['length'] = $this->fs->filesize($path);
            if (extension_loaded("fileinfo")) {
                $mh = finfo_open(FILEINFO_MIME|FILEINFO_PRESERVE_ATIME);
                $ret['type'] = finfo_file($mh, $path);
            } elseif (function_exists('mime_content_type')) {
                $ret['type'] = mime_content_type($path);
            } else {
                # No fileinfo, no mime_magic, so revert to file extension matching.
                # This is a dirty and incomplete method, but I suppose it's better
                # than nothing.  Though only marginally.
                require_once __DIR__.'/stupid_mime.php';
                $ret['type'] = stupid_mime_get_type($path);
            }

        } elseif (strpos($this->enclosure, 'url')    !== false &&
                  strpos($this->enclosure, 'length') !== false &&
                  strpos($this->enclosure, 'type')   !== false) {
            $enc = $this->enclosure;
            $ret = array();
            $ret['url'] = preg_replace('/url\s*=\s*"(.+)"/Ui', '$1', $enc);
            $ret['length'] = preg_replace('/length\s*=\s*"(.+)"/Ui', '$1', $enc);
            $ret['type'] = preg_replace('/type\s*=\s*"(.+)"/Ui', '$1', $enc);
        } else $ret = false;

        return $ret;
    }

    /*
    Method: getPath
    Returns a path to use for the entry.  This is only applicable for
    file-based storage and is for *internal use only*.

    Parameters:
    curr_ts     - The timestamp for this entry.
    just_name   - *Optional* boolean determining whether to return a full path
                  or just a file name.  Default is false.
    long_format - *Optional* boolean for whether to use a long or regular
                  date format.  Defaults to false.

    Returns:
    A string with the path to use for this entry.
    */
    public function getPath($curr_ts, $just_name=false, $long_format=false) {
        $year = date("Y", $curr_ts);
        $month = date("m", $curr_ts);
        $fmt = $long_format ? ENTRY_PATH_FORMAT_LONG : ENTRY_PATH_FORMAT;
        $base = date($fmt, $curr_ts);
        if ($just_name) return $base;
        else return Path::mk($year, $month, $base);
    }

    /*
    Method: setSticky
    Set whether or not an article should be considered "featured".
    Articles not set sticky should be considered archival and not
    shown on things like front-page article lists.

    Parameters:
    show - *Optional* boolean parameter to turn stickiness on or off.
           Default is true (stickiness on).

    Returns:
    True on success, false on failure.
    */
    public function setSticky($show=true) {
        $ret = true;
        $sticky_path = Path::mk(dirname($this->file), STICKY_PATH);
        if ($show) {
            $ret = $this->fs->write_file($sticky_path, $this->subject);
        } elseif ($this->fs->file_exists($sticky_path)) {
            $ret = $this->fs->delete(Path::mk($sticky_path));
        }
        return $ret;
    }

    /*
    Method: isSticky
    Determines if the article is set as sticky.

    Parameters:
    path - *Optional* unique ID for the article.

    Returns:
    True if the article is sticky, false otherwise.
    */
    public function isSticky($path=false) {
        $path = $path ?: dirname($this->file);
        return $this->checkArticlePath($path) &&
            $this->fs->file_exists(Path::mk($path, STICKY_PATH));
    }

    /*
    Method: readSticky
    Get the title and permalink without retreiving the entire article.

    Parameters:
    path - The unique ID for the article.  Defaults to current ID if entry exists.

    Returns:
    A two-element array, with "link" and "title" for the permalink and
    subject of the article.
    */

    public function readSticky($path = null) {
        $old_path = $this->file;
        $path = $path ?: $this->file;
        if ($this->fs->is_dir($path)) {
            $this->file = Path::mk($path, ENTRY_DEFAULT_FILE);
        } else {
           $this->file = $path;
        }
        $sticky_file = Path::mk(dirname($this->file), STICKY_PATH);

        if ( $this->fs->file_exists($sticky_file) ) {
            $data = $this->fs->file($sticky_file);
            $desc = "";
            foreach ($data as $line) {
                $desc .= $line;
            }
            $ret = array("title"=>$desc, "link"=>$this->permalink() );
        } else $ret = false;

        $this->file = $old_path;
        return $ret;
    }

    /*
    Method: isEntry
    Determine if the object is a blog entry or not.

    Parameter:
    path - *Optional* path to the entry.  If not set, use the current object.

    Return:
    True if the object is an existing entry, false otherwise.
    */
    public function isEntry ($path=false) {
        if (! $path) {
            $path = dirname($this->file);
        }
        if (! $path) {
            return false;
        }
        return $this->fs->file_exists(Path::mk($path, ENTRY_DEFAULT_FILE)) ||
               $this->fs->file_exists(Path::mk($path, "current.htm"));
    }

    # Method: isDraft
    # Checks if the given entry is saved as a draft, as opposed to a
    # published blog entry.
    #
    # Returns:
    # True if the entry is a draft, false otherwise.
    public function isDraft($path=false) {
        if (! $path) {
            $path = $this->file;
        }
        if ($this->fs->file_exists($path)) {
           $path = $this->fs->realpath($path);
        }
        $path = dirname($path);
        return ( $this->isEntry($path) &&
                 basename(dirname($path)) == BLOG_DRAFT_PATH );
    }

    # Method: isPublished
    # Determine if the entry has been published.
    #
    # Parameters:
    # path  - The path to the entry.
    #
    # Returns:
    # Whether or not the entry exists and is not a draft.
    public function isPublished($path=false) {
        return $this->isEntry($path) && ! $this->isDraft();
    }

    # Method:
    # Determines if the entry is published as an article.
    #
    # Returns:
    # Whether or not the entry is published as an article.
    public function isArticle() {
        $article_dir = basename(dirname(dirname($this->file)));
        return $this->fs->file_exists($this->file) && $article_dir == BLOG_ARTICLE_PATH;
    }

    public function checkArticlePath($dir_path) {
        $article_dir = basename(dirname($dir_path));
        return $this->fs->is_dir($dir_path) && $article_dir == BLOG_ARTICLE_PATH;
    }

    /*
    Method: localpath
    Get the path to this entry's directory on the local filesystem.  Note
    that this is specific to file-based storage and so should only be c
    called internally.

    Returns:
    A string representing a path to the object or false on failure.
    */
    public function localpath() {
        if (! $this->isEntry()) return false;
        return dirname($this->file);
    }

    /*
    Method: permalink
    Get the permalink to the object.

    Returns:
    A string containing the full URI to this entry.
    */
    public function permalink() {
        return $this->uri("page");
    }

    # Method: commentlink
    # Get the permalink to the object.

    # Returns:
    # A string containing the full URI to this entry.

    public function commentlink() {
        return $this->uri("comment");
    }

    public function setDates($curr_ts = null) {
        # Set the timestamp and date, plus the ones for the original post, if
        # this is a new entry.
        $curr_ts = $curr_ts ? $curr_ts : time();
        $this->timestamp = $curr_ts;
        if (! $this->post_ts) {
            $this->post_ts = $curr_ts;
        }
    }

    # Method: getAutoPublishDate
    # Get the auto-publish date.
    #
    # Returns:
    # The date string when publication will happen or empty string.
    public function getAutoPublishDate() {
        $date = '';
        if ($this->autopublish) {
            $datetime = new DateTime($this->autopublish_date);
            $date = $datetime->format('Y-m-d H:i:s');
        }
        return $date;
    }

    /*
    Method: calcPrettyPermalink
    Calculates a file name for a "pretty" permalink wrapper script.

    Returns:
    The string to be used for the file name.
    */
    public function calcPrettyPermalink() {
        $ret = trim($this->subject);
        $ret = str_replace(array("'", '"'), "", $ret);
        $ret = strtolower($ret);
        $ret = preg_replace("/[^a-z0-9]+/", "-", $ret);
        $ret = trim($ret, '-');
        $ret .= ".php";
        return $ret;
    }

    /*
    Method: calcLegacyPermalink
    Calculates a file name for a "pretty" permalink wrapper script using the
    old, ugly permalink computation.

    Parameters:
    use_broken_regex - *Optional* parameter to calculate the URI based on the
                       ugly regex used in LnBlog < 0.7.  *Defaults* to false.

    Returns:
    The string to be used for the file name.
    */
    public function calcLegacyPermalink($use_broken_regex=false) {
        $ret = trim($this->subject);
        if (!$use_broken_regex) {
            $ret = str_replace(array("'", '"'), "_", $ret);
            $ret = preg_replace("/[^A-Za-z0-9_\-\~]+/", "_", $ret);
        } else {
            $ret = preg_replace("/\W/", "_", $ret);
        }
        if ($ret) {
            $ret .= ".php";
        }
        return $ret;
    }

    /*
    Method: makePrettyPermalink
    Creates a wrapper script that makes a "pretty" permalink to the entry
    directory based on the subject text of the entry.

    Returns:
    True on success, false on failure.
    */
    public function makePrettyPermalink() {
        $subfile = $this->calcPrettyPermalink();
        $ret = false;
        if ($subfile) {
            # Put the wrapper in the parent of the entry directory.
            $path = dirname(dirname($this->file));
            $dir_path = basename(dirname($this->file));
            $path = Path::mk($path, $subfile);
            $content =  "<?php \$entrypath = dirname(__FILE__).DIRECTORY_SEPARATOR.'".$dir_path."'.DIRECTORY_SEPARATOR; " .
                "chdir(\$entrypath); include \$entrypath.'index.php';";
            $ret = $this->fs->write_file($path, $content);
        }
        return $ret;
    }

    /*
    Method: getPostData
    Extract data from an HTTP POST and insert it into the object.
    The data fields in the POST are described below.

    Fields:
    subject    - The subject of the entry, in plain text.
    short_path - The "short path" to access an article.
    abstract   - An abstract of the entry, with markup.
    tags       - A comma-delimited list of free-form tags.
    body       - The entry body, with markup.
    comments   - Boolean representing whether new comments can be posted.
    input_mode - Tristate variable representing the type of markup used.
                 Valid values are defined by the constants <MARKUP_NONE>,
                     <MARKUP_BBCODE>, and <MARKUP_HTML>.
    */
    public function getPostData() {
        if (! has_post()) {
            return false;
        }
        $this->subject = POST("subject");
        $this->abstract = POST("abstract");
        $this->tags = POST("tags");
        $this->data = POST("body");
        $this->allow_comment = POST("comments") ? 1 : 0;
        $this->allow_tb = POST("trackbacks") ? 1 : 0;
        $this->allow_pingback = POST("pingbacks") ? 1 : 0;
        $this->send_pingback = POST("send_pingbacks") ? 1 : 0;
        $this->has_html = POST("input_mode");
        $this->enclosure = POST('hasenclosure') ? POST("enclosure") : '';
        $this->is_article = POST('publisharticle') ? 1 : 0;
        $this->article_path = POST('short_path');
        $this->is_sticky = POST('sticky') ? 1 : 0;
        $this->autopublish = POST('autopublish') ? 1 : 0;
        $this->autopublish_date = POST('autopublishdate');
        foreach ($this->custom_fields as $fld=>$desc) {
            $this->$fld = POST($fld);
        }
        if (! $this->uid) {
            $u = NewUser();
            $this->uid = $u->username();
        }
        $this->raiseEvent("POSTRetreived");
    }

    /*
    Method: exportVars
    Sets template variables with the entry data.

    Parameters:
    tmp - The template we wish to populate.
    */
    public function exportVars(&$tmp, $show_edit_controls=false) {

        $blog = $this->getParent();

        $tmp->set("SUBJECT", $this->subject);
        $tmp->set("POSTDATE", $this->prettyDate($this->post_ts));
        $tmp->set("POST_TIMESTAMP", $this->post_ts);
        $tmp->set("EDITDATE", $this->prettyDate($this->timestamp));
        $tmp->set("EDIT_TIMESTAMP", $this->timestamp);
        $tmp->set("ABSTRACT", $this->getSummary());
        $tmp->set("TAGS", $this->tags());

        $tagurls = array();
        foreach ($this->tags() as $tag) {
            $tagurls[htmlspecialchars($tag)] = $blog->uri("tags", ['tag' =>urlencode($tag)]);
        }
        $tmp->set("TAG_URLS", $tagurls);

        $tmp->set("BODY", $this->markup());
        $tmp->set("ENCLOSURE", $this->enclosure);
        $tmp->set("ENCLOSURE_DATA", $this->getEnclosure());
        $tmp->set("ALLOW_COMMENTS", $this->allow_comment);
        $tmp->set("ALLOW_TRACKBACKS", $this->allow_tb);
        $tmp->set("ALLOW_PINGBACKS", $this->allow_pingback);
        $tmp->set("PERMALINK", $this->permalink());
        $tmp->set("PING_LINK", $this->uri("send_tb"));
        $tmp->set("TRACKBACK_LINK", $this->uri("get_tb"));
        $tmp->set("UPLOAD_LINK", $this->uri("upload"));
        $tmp->set("EDIT_LINK", $this->uri("edit"));
        $tmp->set("DELETE_LINK", $this->uri("delete"));
        $tmp->set("MANAGE_REPLY_LINK", $this->uri("manage_reply"));
        $tmp->set("TAG_LINK", $blog->uri('tags'));
        $tmp->set("COMMENTCOUNT", $this->getCommentCount());
        $tmp->set("COMMENT_LINK", $this->uri("comment"));
        $tmp->set("TRACKBACKCOUNT", $this->getTrackbackCount());
        $tmp->set("SHOW_TRACKBACK_LINK", $this->uri("trackback"));
        $tmp->set("PINGBACKCOUNT", $this->getPingbackCount());
        $tmp->set("PINGBACK_LINK", $this->uri('pingback'));
        $tmp->set("SHOW_CONTROLS", $show_edit_controls);
        $tmp->set("USE_ABSTRACT", $blog->front_page_abstract);
        $send_pingbacks = $this->isEntry() ?
            $this->send_pingback :
            $blog->autoPingbackEnabled();
        $tmp->set("SEND_PINGBACKS", $send_pingbacks);

        # Added so the template can know whether or not to advertise RSS feeds.
        if (PluginManager::instance()->pluginLoaded("RSS2FeedGenerator")) {
            $gen = new RSS2FeedGenerator();
            if ($gen->comment_file) {
                $feed_uri = $this->url_resolver->localpathToUri(Path::mk($this->localpath(), $gen->comment_file), $this->getParent());
                $tmp->set("COMMENT_RSS_ENABLED");
                $tmp->set("COMMENT_FEED_LINK", $feed_uri);
            }
        }

        # RSS compatibility variables
        $tmp->set("TITLE", $this->title());
        $tmp->set("LINK", $this->permalink());
        $tmp->set("DESCRIPTION", $this->description());
        $tmp->set("CATEGORY", $this->tags());

        foreach ($this->custom_fields as $fld=>$desc) {
            $tmp->set(strtoupper($fld), isset($this->$fld) ? $this->$fld : '');
            $tmp->set(strtoupper($fld)."_DESC", $desc);
        }
    }

    /*
    Method: get
    Get the HTML to use to display the entry summary.

    Parameters:
    show_edit_controls - *Optional* boolean determining if the edit, delete,
                         etc. links should be displayed.  *Defaults* to false.

    Returns:
    A string containing the markup.
    */
    public function get(BasePages $web_page, $show_edit_controls=false) {
        ob_start();
        $this->raiseEvent("OnOutput");
        $ret = ob_get_contents();
        ob_end_clean();

        $tmp = NewTemplate("blogentry_summary_tpl.php", $web_page);
        $blog = $this->getParent();
        $usr = NewUser($this->uid);
        $usr->exportVars($tmp);
        $this->exportVars($tmp, $show_edit_controls);

        $ret .= $tmp->process();
        ob_start();
        $this->raiseEvent("OutputComplete");
        $ret .= ob_get_contents();
        ob_end_clean();
        return $ret;
    }

    /*
    Method: getFull
    Get the HTML to use to display the full entry page.

    Parameters:
    show_edit_controls - *Optional* boolean determining if the edit, delete,
                         etc. links should be displayed.  *Defaults* to false.

    Returns:
    A string containing the markup.
    */
    public function getFull($show_edit_controls=false) {
        ob_start();
        $this->raiseEvent("OnOutput");
        $ret = ob_get_contents();
        ob_end_clean();

        $tmp = NewTemplate('blogentry_tpl.php');
        $blog = $this->getParent();
        $usr = NewUser($this->uid);
        $usr->exportVars($tmp);
        $this->exportVars($tmp, $show_edit_controls);
        $tmp->set("COMMENTS", $this->getComments());
        $tmp->set("TRACKBACKS", $this->getTrackbacks());
        $pings = $this->getPingbacksByType();
        $tmp->set("PINGBACKS", $pings['remote']);
        $tmp->set("LOCAL_PINGBACKS", $pings['local']);

        $ret .= $tmp->process();
        ob_start();
        $this->raiseEvent("OutputComplete");
        $ret .= ob_get_contents();
        ob_end_clean();
        return $ret;
    }

    # Generic reply-handling functions.
    # These functions are generic and can get lists of comments, trackbacks, or
    # pingbacks based on given parameters.  The public methods for getting the
    # different kinds of replies are wrappers around these.

    # Method: getReplyCount
    # Get the number of replies of a particular type.
    #
    # Parameters:
    # parms - An associative array of various parameters.  The valid array
    #         keys are "path", which is the directory to scan, "ext", which is
    #         the file extension of the data files, and "match_re", which is a
    #         regular expression matching the names of the data files minus
    #         the extension (default is /[\w\d]+/)).
    #         Note that "path" and "ext" keys are required.
    #
    # Returns:
    # An integer representing the number of replies of the given type.
    # If the call fails for some reason, then false is returned.

    public function getReplyCount($params) {
        $dir_path = dirname($this->file);
        $dir_path = $dir_path.PATH_DELIM.$params['path'];
        $dir_array = $this->fs->scan_directory($dir_path);
        if ($dir_array === false) return false;

        $count = 0;
        foreach ($dir_array as $file) {
            $cond = is_file($dir_path.PATH_DELIM.$file);
            if ($cond) {
                $cond = preg_match("/[\-_\d]+".$params['ext']."/", $file);
                if (! $cond && isset($params['altext'])) {
                    $cond = preg_match("/[\w\d]+".$params['altext']."/", $file);
                }
            }
            if ($cond) $count++;
        }
        return $count;
    }

    # Method: getReplyArray
    # Get an array of replies of a particular type.  This is for internal use
    # only.  Call getComments(), getTrackbacks(), getPingbacks, or getReplies()
    # instead of this.
    #
    # Parameters:
    # params - An associative array of settings, as in <getReplyCount>.  In
    #          *addition* to the keys allowed by that function, this one also
    #          requires a "creator" key, which is the name of a function that
    #          will return the correct type of object given the data path to
    #          its storage file as a parameter.  It also accepts an optional
    #          "sort_asc" key, which will sort the files in order by filename,
    #          (which equates to date order) rather than in reverse order when
    #          set to true.
    #
    # Returns:
    # An array of BlogComment, Trackback, or Pingback objects, depending on
    # the parameters.  Returns false on failure.

    public function getReplyArray($params) {
        $dir_path = dirname($this->file);
        $dir_path = $dir_path.PATH_DELIM.$params['path'];
        if (! is_dir($dir_path)) return array();
        else $reply_dir = $this->fs->scan_directory($dir_path);

        $reply_files = array();
        foreach ($reply_dir as $file) {
            $cond = is_file($dir_path.PATH_DELIM.$file);
            if ($cond) {
                $cond = preg_match("/[\-_\d]+".$params['ext']."/", $file);
                if (! $cond && isset($params['altext'])) {
                    $cond = preg_match("/[\w\d]+".$params['altext']."/", $file);
                }
            if ($cond) $reply_files[] = $file;
            }
        }
        if (isset($params['sort_asc']) && $params['sort_asc']) {
            sort($reply_files);
        } else {
            rsort($reply_files);
        }

        $reply_array = array();
        $creator = $params['creator'];
        foreach ($reply_files as $file)
            $reply_array[] = $creator($dir_path.PATH_DELIM.$file);

        return $reply_array;
    }

    /*
    Method: getAllReplies
    Gets an array of all replies to this entry.

    Returns:
    An array of BlogComment, Trackback, and Pingback objects.
    */
    public function getReplies() {
        $repls = array();
        $repls = array_merge($repls, $this->getComments());
        $repls = array_merge($repls, $this->getTrackbacks());
        $repls = array_merge($repls, $this->getPingbacks());
        return $repls;
    }

    # Comment handling functions.

    /*
    Method: getCommentCount
    Determine the number of comments that belong to this object.

    Returns:
    A non-negative integer representing the number of comments or false on
    failure.
    */
    public function getCommentCount() {
        $params = array('path'=>ENTRY_COMMENT_DIR, 'ext'=>COMMENT_PATH_SUFFIX, 'altext'=>'.txt');
        return $this->getReplyCount($params);
    }

    /*
    Method: getComments
    Gets all the comment objects for this entry.

    Parameters:
    sort_asc - *Optional* boolean (true by default) determining whether the
               comments should be sorted in ascending order by date.

    Returns:
    An array of BlogComment object.
    */
    public function getComments($sort_asc=true) {
        $params = array('path'=>ENTRY_COMMENT_DIR, 'ext'=>COMMENT_PATH_SUFFIX, 'altext'=>'.txt',
                        'creator'=>'NewBlogComment', 'sort_asc'=>$sort_asc);
        return $this->getReplyArray($params);
    }

    # Method: getCommentArray
    # Compatibility function, alias for getComments
    public function getCommentArray($sort_asc=true) {
        return $this->getComments($sort_asc);
    }

    # TrackBack handling functions.

    /*
    Method: getTrackbackCount
    Get the number of TrackBacks for this object.

    Returns:
    A non-negative integer representing the number of TrackBacks or false on
    failure.
    */
    public function getTrackbackCount() {
        $params = array('path'=>ENTRY_TRACKBACK_DIR, 'ext'=>TRACKBACK_PATH_SUFFIX);
        return $this->getReplyCount($params);
    }

    /*
    Method: getTrackbacks
    Get an array that contains all TrackBacks for this entry.

    Parameters:
    sort_asc - *Optional* boolean (true by default) determining whether the
               trackbacks should be sorted in ascending order by date.

    Returns:
    An array of Trackback objects.
    */
    public function getTrackbacks($sort_asc=true) {
        $params = array('path'=>ENTRY_TRACKBACK_DIR,
                        'ext'=>TRACKBACK_PATH_SUFFIX,
                        'creator'=>'NewTrackback', 'sort_asc'=>$sort_asc);
        return $this->getReplyArray($params);
    }

    # Method: getTrackbackArray
    # Compatibility function, alias for getTrackbacks
    public function getTrackbackArray($sort_asc=true) {
        return $this->getTrackbacks($sort_asc);
    }

    # Pingback handling functions

    /*
    Method: getPingbackCount
    Get the number of Pingbacks for this object.

    Returns:
    A non-negative integer representing the number of Pingbacks or false on
    failure.
    */
    public function getPingbackCount() {
        $params = array('path'=>ENTRY_PINGBACK_DIR, 'ext'=>PINGBACK_PATH_SUFFIX);
        return $this->getReplyCount($params);
    }

    /*
    Method: getPingbacks
    Get an array that contains all Pingbacks for this entry.

    Parameters:
    sort_asc - *Optional* boolean (true by default) determining whether the
               pingbacks should be sorted in ascending order by date.

    Returns:
    An array of Pingback objects.
    */
    public function getPingbacks($sort_asc=true) {
        $params = array('path'=>ENTRY_PINGBACK_DIR,
                        'ext'=>PINGBACK_PATH_SUFFIX,
                        'creator'=>'NewPingback', 'sort_asc'=>$sort_asc);
        return $this->getReplyArray($params);
    }

    # Method: getPingbackArray
    # Compatibility function, alias for getPingbacks
    public function getPingbackArray($sort_asc=true) {
        return $this->getPingbacks($sort_asc);
    }

    # Method: getPingbacksByType
    # Gets the local and remote pingbacks for an entry, i.e. pingbacks that come
    # from URLs on the same blog as this entry and others.
    #
    # Returns:
    # An associative array with two keys, "local" and "remote".  Each element is
    # an array of Pingback objects with the "friendly" pings in the "local"
    # array and others in the "remote" array.

    public function getPingbacksByType() {
        $pings = $this->getPingbacks();
        $ret = array('local'=>array(), 'remote'=>array());
        $target = parse_url($this->permalink());
        foreach ($pings as $key=>$p) {
            $source = parse_url($p->source);
            if ($source['host'] == $target['host']) {
                $ret['local'][] = $p;
            } else {
                $ret['remote'][] = $p;
            }
        }
        return $ret;
    }

    # Method: pingExists
    # Checks if a Pingback ping has already been recorded for the source URL.
    #
    # Parameters:
    # uri - The source URI to check.
    #
    # Returns:
    # True if there is already a recorded ping with the source URI, false
    # otherwise.

    public function pingExists($uri) {
        $pings = $this->getPingbacks();
        if (! $pings) {
            return false;
        }
        foreach ($pings as $p) {
            if ($p->source == $uri) {
                return true;
            }
        }
        return false;
    }

    # Method: writeFileData
    # Overrides the default implementation to make sure that we always
    # write to the correct file name.
    public function writeFileData() {
        $file_base_name = basename($this->file);
        if ($file_base_name != ENTRY_DEFAULT_FILE) {
            $this->file = Path::mk(dirname($this->file), ENTRY_DEFAULT_FILE);
        }
        return parent::writeFileData();
    }

    # Method: getAttachments
    # Get a list of files attached to this entry
    #
    # Returns: Array of AttachedFile objects
    public function getAttachments() {
        return $this->filemanager->getAll();
    }

    # Method: addAttachment
    # Add an attached file to the entry.
    #
    # Parameters:
    # path - The local file path to the file to attach.
    # name - Optional name of the file.  Default is the existing name.
    public function addAttachment($path, $name = '') {
        $this->filemanager->attach($path, $name);
    }

    # Method: removeAttachment
    # Remove a file that is attached to the entry.
    #
    # Parameter:
    # name - The name of the file to remove.
    public function removeAttachment($name) {
        $this->filemanager->remove($name);
    }

    # Method: getManagedFiles
    # Get a list of system files that are managed by the entry and
    # are therefore excluded from uploads.
    #
    # Returns: An array of file names.
    public function getManagedFiles() {
        return ['entry.xml', 'index.php', 'sticky.txt'];
    }

    # Method: addReply
    # Inserts a reply (comment, pingback, etc.) to the entry.
    #
    # Parameters:
    # reply - The reply object ot insert
    public function addReply($reply) {
        $result = $reply->insert($this);
    }

    private function getPathFromGlobalId($globalid) {
        $blogs = SystemConfig::instance()->blogRegistry();
        foreach ($blogs as $blogid => $urlpath) {
            if (strpos($globalid, $blogid) === 0) {
                $relpath = substr_replace($globalid, '', 0, strlen($blogid));
                $path = Path::mk($urlpath->path(), $relpath);
                if ($this->fs->file_exists($path)) {
                    return $path;
                }
            }
        }
        return false;
    }
}
