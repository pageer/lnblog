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

require_once("lib/utils.php");

class RSS2Entry {
	
	var $link;
	var $title;
	var $description;
	var $author;
	var $category;
	var $pub_date;
	var $comments;
	var $comment_rss;
	var $comment_count;
	var $guid;
	
	function RSS2Entry($link="", $title="", $desc="", $comm="", $guid="", $auth="", $cat="", $pdate="", $cmtrss="", $cmtcount="") {
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
		$this->guid = $guid;
	}

	function get() {
		$ret = "<item>\n";
		if ($this->title) {
			$ret .= "<title>".$this->title."</title>\n";
		}
		if ($this->link) {
			$ret .= "<link>".$this->link."</link>\n";
		}
		if ($this->pub_date) {
			$ret .= "<pubDate>".$this->pub_date."</pubDate>";
		}
		if ($this->description) {
			$ret .= "<description>\n".$this->description."\n</description>\n";
		}
		if ($this->author) {
			$ret .= "<author>".$this->author."</author>\n";
		}
		if ($this->category) {
			$ret .= "<category>".$this->category."</category>\n";
		}
		if($this->comment_count) {
			$ret .= "<slash:comments>".$this->comment_count."</slash:comments>\n";
		}
		if ($this->comments) {
			$ret .= "<comments>".$this->comments."</comments>\n";
		}
		if ($this->comment_rss) {
			$ret .= "<wfw:commentRss>".$this->comment_rss."</wfw:commentRss>";
		}
		if ($this->guid) {
			$ret .= "<guid";
			if (RSS2GENERATOR_GUID_IS_PERMALINK) $ret .= ' isPermaLink="true"';
			$ret .= ">".$this->guid."</guid>\n";
		}
		$ret .= "</item>\n";
		return $ret;
	}

}

class RSS2 {

	var $url;
	var $title;
	var $description;
	var $image;
	var $entrylist;

	function RSS2() {
		$this->url = "";
		$this->title = "";
		$this->description = "";
		$this->image = "";
		$this->entrylist = array();
	}

	function addEntry($ent) {
		$this->entrylist[] = $ent;
	}

	function get() {
		
		$list_text = "";
		$detail_text = "";
		foreach ($this->entrylist as $ent) $detail_text .= $ent->get();
	
		$ret = '<?xml version="1.0" encoding="utf-8"?>'."\n".
			'<rss version="2.0" xmlns:slash="http://purl.org/rss/1.0/modules/slash/" xmlns:wfw="http://wellformedweb.org/CommentAPI/">'."\n";
		$ret .= "<channel>\n";
		$ret .= '<link>'.$this->url."</link>\n";
		$ret .= "<title>".$this->title."</title>\n";
		$ret .= "<description>".$this->description."</description>\n";
		$ret .= "<generator>".PACKAGE_NAME." ".PACKAGE_VERSION."</generator>\n";
		$ret .= $detail_text;
		$ret .= "</channel>\n</rss>";

		return $ret;
	}

	function writeFile($path) {
		$fs = NewFS();
		$content = $this->get();
		$ret = $fs->write_file($path, $content);
		$fs->destruct();
		return $ret;
	}

}

class RSS2FeedGenerator extends Plugin {

	var $guid_is_permalink;

	function RSS2FeedGenerator() {
		$this->plugin_desc = _("Create RSS 2.0 feeds for comments and blog entries.");
		$this->plugin_version = "0.1.0";
		$this->guid_is_permalink = true;
		$this->member_list = array("guid_is_permalink");
		if (! defined("RSS2GENERATOR_GUID_IS_PERMALINK"))
			define("RSS2GENERATOR_GUID_IS_PERMALINK", $this->guid_is_permalink);
	}

	function updateCommentRSS2($cmt) {
		$parent = $cmt->getParent();
		$feed = new RSS2();
		$comment_path = $parent->localpath().PATH_DELIM.ENTRY_COMMENT_DIR;
		$path = $comment_path.PATH_DELIM.COMMENT_RSS2_PATH;
		$feed_url = localpath_to_uri($path);

		$feed->url = $feed_url;
		#$feed->image = $this->image;
		$feed->description = $parent->subject;
		$feed->title = $parent->subject;
	
		$comm_list = $parent->getCommentArray();
		foreach ($comm_list as $ent) 
			$feed->entrylist[] = new RSS2Entry($ent->permalink(), 
				$ent->subject, 
				"<![CDATA[".$ent->markup($ent->data)."]]>");
		
		$ret = $feed->writeFile($path);	
		return $ret;
	}

	function updateBlogRSS2 ($entry) {

		$usr = NewUser();
		$feed = new RSS2();
		$blog = $entry->getParent();
		$path = $blog->home_path.PATH_DELIM.BLOG_FEED_PATH.PATH_DELIM.BLOG_RSS2_NAME;
		$feed_url = localpath_to_uri($path);

		$feed->url = $feed_url;
		$feed->image = $blog->image;
		$feed->description = $blog->description;
		$feed->title = $blog->name;
	
		if (! $blog->entrylist) $blog->getRecent($blog->max_rss);
		foreach ($blog->entrylist as $ent) {
			$usr->get($ent->uid);
			$author_data = $usr->email();
			if ( $author_data ) $author_data .= " (".$usr->displayName().")";
			$cmt_count = $ent->getCommentCount();
			$feed->entrylist[] = new RSS2Entry($ent->permalink(), 
				$ent->subject, 
				"<![CDATA[".$ent->markup($ent->data)."]]>", 
				$ent->commentlink(),
				$ent->permalink(), 
				$author_data, "", "", 
				$cmt_count ? $ent->commentlink().COMMENT_RSS2_PATH : "", 
				$cmt_count );
		}
		
		$ret = $feed->writeFile($path);	
		if (! $ret) return  UPDATE_RSS1_ERROR;
		else return UPDATE_SUCCESS;
	}
	
}

$gen = new RSS2FeedGenerator();
$gen->registerEventHandler("blogcomment", "InsertComplete", "updateCommentRSS2");
$gen->registerEventHandler("blogcomment", "UpdateComplete", "updateCommentRSS2");
$gen->registerEventHandler("blogcomment", "DeleteComplete", "updateCommentRSS2");
$gen->registerEventHandler("blogentry", "InsertComplete", "updateBlogRSS2");
$gen->registerEventHandler("blogentry", "UpdateComplete", "updateBlogRSS2");
$gen->registerEventHandler("blogentry", "DeleteComplete", "updateBlogRSS2");

?>
