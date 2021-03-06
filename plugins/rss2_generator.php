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

# Plugin: RSS2FeedGenerator
# This plugin generates RSS 2.0 feeds for a blog.  It creates RSS feeds for blog entries
# and comment feeds on each entry. There is also an option, enabled by default,
# to generate entry feeds for each tag.
#
# Note that this plugin does *not* dynamically generate the feeds.  It works by building
# up the feed and writing it to a static file on disk.
#
# There are options for controlling the names of the feed
# files and for linking XSL or CSS stylesheet to link to the RSS feeds.  Those are
# optional, but they can make the feed file somewhat nicer looking if it is viewed in a web
# browser.

class RSS2Entry
{

    public $link;
    public $title;
    public $description;
    public $author;
    public $category;
    public $pub_date;
    public $comments;
    public $comment_rss;
    public $comment_count;
    public $enclosure;
    public $guid;

    function __construct($link="", $title="", $desc="", $comm="", $guid="",
                       $auth="", $cat="", $pdate="", $cmtrss="", $cmtcount="",
                       $enclos=false) {
        $this->link = $link;
        $this->title = $title;
        if ($desc) $this->description = $desc;
        else $this->description = $this->title;
        $this->author = $auth;
        $this->category = $cat;
        $this->pub_date = $pdate;
        $this->comments = $comm;
        $this->comment_rss = $cmtrss;
        $this->comment_count = $cmtcount;
        $this->enclosure = $enclos;
        $this->guid = $guid;
    }

    function get() {
        $ret = "<item>\n";
        if ($this->title) {
            $ret .= "<title>".htmlspecialchars($this->title)."</title>\n";
        }
        if ($this->link) {
            $ret .= "<link>".$this->link."</link>\n";
        }
        if ($this->pub_date) {
            $ret .= "<pubDate>".$this->pub_date."</pubDate>";
        }
        if ($this->description) {
            $ret .= "<description>\n".htmlspecialchars($this->description)."\n</description>\n";
        }
        if ($this->author) {
            $ret .= "<author>".htmlspecialchars($this->author)."</author>\n";
        }
        if ($this->category) {
            if (is_array($this->category)) {
                foreach ($this->category as $cat)
                    $ret .= '<category>'.htmlspecialchars($cat)."</category>\n";
            } else {
                $ret .= "<category>".htmlspecialchars($this->category)."</category>\n";
            }
        }
        if ($this->comment_count) {
            $ret .= "<slash:comments>".$this->comment_count."</slash:comments>\n";
        }
        if ($this->comments) {
            $ret .= "<comments>".$this->comments."</comments>\n";
        }
        if ($this->comment_rss) {
            $ret .= "<wfw:commentRss>".$this->comment_rss."</wfw:commentRss>\n";
        }
        if ($this->enclosure) {
            $ret .= '<enclosure url="'.$this->enclosure['url'].'" '.
                    'length="'.$this->enclosure['length'].'" '.
                    'type="'.$this->enclosure['type'].'" />'."\n";
        }
        if ($this->guid) {
            #$ret .= "<guid";
            #if (RSS2GENERATOR_GUID_IS_PERMALINK) $ret .= ' isPermaLink="true"';
            $ret .= "<guid>".$this->guid."</guid>\n";
        }
        $ret .= "</item>\n";
        return $ret;
    }

}

class RSS2
{

    public $url;
    public $title;
    public $description;
    public $image;
    public $entrylist;
    public $link_stylesheet;
    public $link_xslsheet;

    function __construct() {
        $this->url = "";
        $this->title = "";
        $this->description = "";
        $this->image = "";
        $this->link_stylesheet = '';
        $this->link_xslsheet = '';
        $this->entrylist = array();
    }

    function addEntry($ent) {
        $this->entrylist[] = $ent;
    }

    function get() {

        $list_text = "";
        $detail_text = "";
        foreach ($this->entrylist as $ent) $detail_text .= $ent->get();

        $ret = '<?xml version="1.0" encoding="utf-8"?>'."\n";
        if ($this->link_stylesheet) {
            $sheet = '../index.php?style=' . $this->link_stylesheet;
            $ret .= '<?xml-stylesheet type="text/css" href="'.$sheet.'" ?>'."\n";
        }
        if ($this->link_xslsheet) {
            $sheet = '../index.php?style=' . $this->link_xslsheet;
            $ret .= '<?xml-stylesheet type="text/xsl" href="'.$sheet.'" ?>'."\n";
        }
        $ret .= '<rss version="2.0" xmlns:slash="http://purl.org/rss/1.0/modules/slash/" xmlns:wfw="http://wellformedweb.org/CommentAPI/">'."\n";
        $ret .= "<channel>\n";
        $ret .= '<link>'.$this->url."</link>\n";
        $ret .= "<title>".htmlspecialchars($this->title)."</title>\n";
        $ret .= "<description>".htmlspecialchars($this->description)."</description>\n";
        $ret .= "<generator>".PACKAGE_NAME." ".PACKAGE_VERSION."</generator>\n";
        $ret .= $detail_text;
        $ret .= "</channel>\n</rss>";

        return $ret;
    }

    function writeFile($path) {
        $fs = NewFS();
        $content = $this->get();
        $ret = $fs->write_file($path, $content);
        return $ret;
    }

}

class RSS2FeedGenerator extends Plugin
{
    public $feed_style;
    public $feed_xsl;
    public $feed_file;
    public $topic_feeds;
    public $cat_suffix;
    public $guid_is_permalink;
    public $comment_file = '';

    public function __construct() {
        $this->plugin_desc = _("Create RSS 2.0 feeds for comments and blog entries.");
        $this->plugin_version = "0.3.2";
        $this->guid_is_permalink = true;
        #$this->addOption("guid_is_permalink",
        #                 _("Use entry permalink as globally unique identifier"),
        #                 true, "checkbox");
        $this->addOption(
            "feed_style", _("CSS style sheet for RSS feeds"),
            "", "text"
        );
        $this->addOption(
            "feed_xsl", _("XSLT style sheet for RSS feeds"),
            "", "text"
        );
        $this->addOption(
            "feed_file", _("File name for blog RSS 2 feed"),
            "news.xml", "text"
        );
        $this->addOption(
            "comment_file", _("File name for comment RSS 2 feeds"),
            "comments.xml", "text"
        );
        $this->addOption(
            "topic_feeds", _("Generate feeds for each topic"),
            true, 'checkbox'
        );
        $this->addOption(
            "cat_suffix",
            _("Suffix to append to category for top feed files"),
            "_news.xml", "text"
        );
        parent::__construct();
        #if (! defined("RSS2GENERATOR_GUID_IS_PERMALINK"))
        #   define("RSS2GENERATOR_GUID_IS_PERMALINK", $this->guid_is_permalink);
    }

    function updateCommentRSS2 ($cmt) {
        $resolver = new UrlResolver();

        if (is_a($cmt, "BlogComment")) {
            $parent = $cmt->getParent();
        } else {
            $parent = $cmt;
        }
        $blog = $parent->getParent();

        $feed = new RSS2();
        $feed->link_stylesheet = $this->feed_style;
        $feed->link_xslsheet = $this->feed_xsl;
        $comment_path = $parent->localpath().PATH_DELIM.ENTRY_COMMENT_DIR;
        $path = $comment_path.PATH_DELIM.$this->comment_file;
        $feed_url = $resolver->localpathToUri($path, $blog);

        $feed->url = $feed_url;
        #$feed->image = $this->image;
        $feed->description = $parent->subject;
        $feed->title = $parent->subject;

        $comm_list = $parent->getCommentArray();
        foreach ($comm_list as $ent)
            $feed->entrylist[] = new RSS2Entry(
                $ent->permalink(),
                $ent->subject,
                $ent->markup($ent->data),
                "", $ent->permalink()
            );

        $ret = $feed->writeFile($path);
        return $ret;
    }

    function checkEnclosure(&$entry) {

    }

    function updateBlogRSS2 (&$entry) {
        $resolver = new UrlResolver();
        $usr = NewUser();
        $feed = new RSS2();
        $feed->link_stylesheet = $this->feed_style;
        $feed->link_xslsheet = $this->feed_xsl;
        $blog = $entry->getParent();
        $path = $blog->home_path.PATH_DELIM.BLOG_FEED_PATH.PATH_DELIM.$this->feed_file;
        $feed_url = $resolver->localpathToUri($path, $blog);

        $feed->url = $feed_url;
        $feed->image = $blog->image;
        $feed->description = $blog->description;
        $feed->title = $blog->name;

        #$enclosure_data = $entry->getEnclosure();
        #echo $entry->enclosure;
        #print_r($enclosure_data);
        #if ($entry->enclosure && ! $enclosure_data) {
        #   $this->registerEventHandler("fileupload", "MoveComplete", "UpdateBlogRSS2");
        #}

        if (! $blog->entrylist) $blog->getRecent($blog->max_rss);
        foreach ($blog->entrylist as $ent) {
            $usr = NewUser($ent->uid);
            $author_data = $usr->email();
            if ( $author_data ) $author_data .= " (".$usr->displayName().")";
            $cmt_count = $ent->getCommentCount();

            # If there's an enclosure, but we can't get it's data, then it might
            # be a file uploaded with the entry, so set an upload event listener.

            $feed->entrylist[] = new RSS2Entry(
                $ent->permalink(),
                $ent->subject,
                $ent->markup($ent->data),
                $ent->commentlink(),
                $ent->uri('base'),
                $author_data, $ent->tags(), "",
                $cmt_count ? $ent->commentlink().$this->comment_file : "",
                $cmt_count, $ent->getEnclosure()
            );
        }

        $ret = $feed->writeFile($path);
        if (! $ret) return  UPDATE_RSS1_ERROR;
        else return UPDATE_SUCCESS;
    }

    function updateBlogRSS2ByEntryTopic (&$entry) {
        $resolver = new UrlResolver();
        $blog = $entry->getParent();
        $ret = true;

        if (! $entry->tags()) return false;

        foreach ($entry->tags() as $tag) {

            $usr = NewUser();
            $feed = new RSS2();
            $feed->link_stylesheet = $this->feed_style;
            $feed->link_xslsheet = $this->feed_xsl;
            $topic = preg_replace('/\W/', '', $tag);
            $path = Path::mk($blog->home_path, BLOG_FEED_PATH, $topic.$this->cat_suffix);
            $feed_url = $resolver->localpathToUri($path, $blog);

            $feed->url = $feed_url;
            $feed->image = $blog->image;
            $feed->description = $blog->description;
            $feed->title = $blog->name;

            # If there's an enclosure, but we can't get it's data, then it might
            # be a file uploaded with the entry, so set an upload event listener.
            #$enclosure_data = $entry->getEnclosure();
            #if ($entry->enclosure && ! $enclosure_data) {
            #   $this->registerEventHandler("fileupload", "MoveComplete", "UpdateBlogRSS2ByEntryTopic");
            #}

            if (! $blog->entrylist) $blog->getEntriesByTag(array($tag), $blog->max_rss);
            foreach ($blog->entrylist as $ent) {
                $usr = NewUser($ent->uid);
                $author_data = $usr->email();
                if ( $author_data ) $author_data .= " (".$usr->displayName().")";
                $cmt_count = $ent->getCommentCount();
                                $feed->entrylist[] = new RSS2Entry(
                                    $ent->permalink(),
                                    $ent->subject,
                                    $ent->markup($ent->data),
                                    $ent->commentlink(),
                                    $ent->uri('base'),
                                    $author_data, "", "",
                                    $cmt_count ? $ent->commentlink().$this->comment_file : "",
                                    $cmt_count, $ent->getEnclosure() 
                                );
            }

            $ret = $feed->writeFile($path);
            if (! $ret) $ret = $ret && UPDATE_RSS1_ERROR;
            else $ret = $ret && UPDATE_SUCCESS;
        }
        return $ret;
    }

}

$gen = new RSS2FeedGenerator();
$gen->registerEventHandler("blogcomment", "InsertComplete", "updateCommentRSS2");
$gen->registerEventHandler("blogcomment", "UpdateComplete", "updateCommentRSS2");
$gen->registerEventHandler("blogcomment", "DeleteComplete", "updateCommentRSS2");
$gen->registerEventHandler("blogentry", "InsertComplete", "updateBlogRSS2");
$gen->registerEventHandler("blogentry", "InsertComplete", "updateCommentRSS2");
$gen->registerEventHandler("blogentry", "UpdateComplete", "updateBlogRSS2");
$gen->registerEventHandler("blogentry", "DeleteComplete", "updateBlogRSS2");
if ($gen->topic_feeds) {
    $gen->registerEventHandler("blogentry", "InsertComplete", "updateBlogRSS2ByEntryTopic");
    $gen->registerEventHandler("blogentry", "UpdateComplete", "updateBlogRSS2ByEntryTopic");
    $gen->registerEventHandler("blogentry", "DeleteComplete", "updateBlogRSS2ByEntryTopic");
}
