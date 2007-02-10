<?php
require_once("lib/utils.php");
# Plugin: RSS1FeedGenerator
# Generates RSS 1.0 (RDF Site Summary) feeds for a blog.  This generates both 
# feeds for new entries and comment feeds for each individual entry.  There is 
# also an option (enabled by default) to generate a feed for each topic contained
# in the blog.  There are also options to set the names of the feed files, if 
# you so desire.

class RSS1Entry {
	
	var $link;
	var $title;
	var $description;
	
	function RSS1Entry($link="", $title="", $desc="") {
		$this->link = $link;
		$this->title = $title;
		if ($desc) $this->description = $desc;
		else $this->description = $this->title;
	}

	function getListItem() {
		return '<rdf:li resource="'.$this->link.'" />';
	}

	function get() {
		$ret = '<item rdf:about="'.$this->link."\">\n";
		$ret .= "<title>".htmlspecialchars($this->title)."</title>\n";
		$ret .= "<link>".$this->link."</link>\n";
		$ret .= "<description>".htmlspecialchars($this->description)."</description>\n";
		$ret .= "</item>\n";
		return $ret;
	}

}

# Class for generating RDF Site Summary output.

class RSS1 {

	var $url;
	var $site;
	var $title;
	var $description;
	var $image;
	var $entrylist;

	function RSS1() {
		$this->url = "";
		$this->site = "";
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
		foreach ($this->entrylist as $ent) {
			$list_text .= $ent->getListItem();
			$detail_text .= $ent->get();
		}
	
		$ret = '<?xml version="1.0" encoding="utf-8"?>'."\n".
			'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'.
			' xmlns="http://purl.org/rss/1.0/">'."\n";
		$ret .= '<channel rdf:about="'.$this->url."\">\n";
		$ret .= "<title>".htmlspecialchars($this->title)."</title>\n";
		$ret .= "<link>".$this->site."</link>\n";
		$ret .= "<description>".htmlspecialchars($this->description)."</description>\n";
		if ($this->image) 
			$ret .= '<image rdf:resource="'.$this->image."\" />\n";
		$ret .= "<items>\n<rdf:Seq>\n".$list_text."</rdf:Seq>\n</items>";
		$ret .= "\n</channel>\n";
		if ($this->image) {
			$ret .= '<image rdf:about="'.$this->url."\">\n";
			$ret .= "<title>".htmlspecialchars($this->title)."</title>\n";
			$ret .= "<link>".$this->site."</link>\n";
			$ret .= "<url>".$this->image."</url>\n</image>\n";
		}
		$ret .= $detail_text."</rdf:RDF>";

		return $ret;
	}

	function writeFile($path) {
		$fs = NewFS();
		$content = $this->get();
		$ret = $fs->write_file($path, $content);
		return $ret;
	}

}

class RSS1FeedGenerator extends Plugin {

	function RSS1FeedGenerator() {
		$this->plugin_desc = _("Create RSS 1.0 feeds for comments and blog entries.");
		$this->plugin_version = "0.2.0";
		$this->addOption("feed_file", _("The file name for the blog RSS feed"),
		                 "news.rdf", "text");
		$this->addOption("comment_file", _("The file name for comment RSS feeds"),
		                 "comments.rdf", "text");
		$this->addOption("topic_feeds", _("Generate feeds for each topic"),
		                 true, 'checkbox');
		$this->getConfig();
	}

	function updateCommentRSS1(&$cmt) {
		
		#if (method_exists($cmt, 'isComment') && $cmt->isComment()) {
		if (is_a($cmt, "BlogComment")) {
			$parent = $cmt->getParent();
		} else {
			$parent = $cmt;
		}
#echo get_class($cmt).", ".get_class($parent).", ".($cmt->isComment()?"yes":"no");
		$blog = $parent->getParent();
		$feed = new RSS1();
		$comment_path = $parent->localpath().PATH_DELIM.ENTRY_COMMENT_DIR;
		
		$path = $comment_path.PATH_DELIM.$this->comment_file;
		$feed_url = localpath_to_uri($path);

		$feed->url = localpath_to_uri($path);
		#$feed->image = $this->image;
		$feed->title = $parent->subject;
		$feed->description = $parent->subject;
		$feed->site = $blog->uri('blog');
	
		$comm_list = $parent->getCommentArray();
		foreach ($comm_list as $ent) 
			$feed->entrylist[] = new RSS1Entry($ent->permalink(), $ent->subject, $ent->subject);
		
		$ret = $feed->writeFile($path);	
		return $ret;
	}

	function updateBlogRSS1 ($entry) {

		$feed = new RSS1();
		$blog = $entry->getParent();
		$path = $blog->home_path.PATH_DELIM.BLOG_FEED_PATH.PATH_DELIM.$this->feed_file;
		$feed_url = localpath_to_uri($path);

		$feed->url = $feed_url;
		$feed->image = $blog->image;
		$feed->title = $blog->name;
		$feed->description = $blog->description;
		$feed->site = $blog->getURL();
	
		if (! $blog->entrylist) $blog->getRecent($blog->max_rss);
		foreach ($blog->entrylist as $ent) 
			$feed->entrylist[] = new RSS1Entry($ent->permalink(), $ent->subject, $ent->subject);
		
		$ret = $feed->writeFile($path);
		if (! $ret) return UPDATE_RSS1_ERROR;
		else return UPDATE_SUCCESS;
	}

	function updateBlogRSS1ByEntryTopic ($entry) {

		$blog = $entry->getParent();
		$ret = true;

		if (! $entry->tags()) return false;

		foreach ($entry->tags() as $tag) {

			$feed = new RSS1();
			$topic = preg_replace('/\W/','',$tag);
			$file = $topic.'_'.$this->feed_file;
			$path = mkpath($blog->home_path,BLOG_FEED_PATH,$file);
			$feed_url = localpath_to_uri($path);

			$feed->url = $feed_url;
			$feed->image = $blog->image;
			$feed->title = spf_('%s - %s', $blog->name, $topic);
			$feed->description = $blog->description;
			$feed->site = $blog->getURL();
	
			if (! $blog->entrylist) $blog->getEntriesByTag(array($tag), $blog->max_rss);
			foreach ($blog->entrylist as $ent) 
				$feed->entrylist[] = new RSS1Entry($ent->permalink(), $ent->subject, $ent->subject);
		
			$ret = $feed->writeFile($path);
			if (! $ret) $ret &= UPDATE_RSS1_ERROR;
			else $ret &= UPDATE_SUCCESS;
		}
		return $ret;	
	}

}

$gen =& new RSS1FeedGenerator();
$gen->registerEventHandler("blogcomment", "InsertComplete", "updateCommentRSS1");
$gen->registerEventHandler("blogcomment", "UpdateComplete", "updateCommentRSS1");
$gen->registerEventHandler("blogcomment", "DeleteComplete", "updateCommentRSS1");
$gen->registerEventHandler("blogentry", "InsertComplete", "updateBlogRSS1");
$gen->registerEventHandler("blogentry", "InsertComplete", "updateCommentRSS1");
$gen->registerEventHandler("blogentry", "UpdateComplete", "updateBlogRSS1");
$gen->registerEventHandler("blogentry", "DeleteComplete", "updateBlogRSS1");
if ($gen->topic_feeds) {
	$gen->registerEventHandler("blogentry", "InsertComplete", "updateBlogRSS1ByEntryTopic");
	$gen->registerEventHandler("blogentry", "UpdateComplete", "updateBlogRSS1ByEntryTopic");
	$gen->registerEventHandler("blogentry", "DeleteComplete", "updateBlogRSS1ByEntryTopic");
}

?>
