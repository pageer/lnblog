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

require_once("blogconfig.php");
require_once("lib/utils.php");
require_once("lib/lnblogobject.php");

# Entries for RDF Site Summary documents.

class RSS1Entry extends LnBlogObject {
	
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
		$ret .= "<title>".$this->title."</title>\n";
		$ret .= "<link>".$this->link."</link>\n";
		$ret .= "<description>".$this->description."</description>\n";
		$ret .= "</item>\n";
		return $ret;
	}

}

# Class for generating RDF Site Summary output.

class RSS1 extends LnBlogObject {

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
		$ret .= "<title>".$this->title."</title>\n";
		$ret .= "<link>".$this->site."</link>\n";
		$ret .= "<description>".$this->description."</description>\n";
		if ($this->image) 
			$ret .= '<image rdf:resource="'.$this->image."\" />\n";
		$ret .= "<items>\n<rdf:Seq>\n".$list_text."</rdf:Seq>\n</items>";
		$ret .= "\n</channel>\n";
		if ($this->image) {
			$ret .= '<image rdf:about="'.$this->url."\">\n";
			$ret .= "<title>".$this->title."</title>\n";
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

class RSS2Entry extends LnBlogObject {
	
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
			if (GUID_IS_PERMALINK) $ret .= ' isPermaLink="true"';
			$ret .= ">".$this->guid."</guid>\n";
		}
		$ret .= "</item>\n";
		return $ret;
	}

}

class RSS2 extends LnBlogObject {

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
			'<rss version="2.0">'."\n";
		$ret .= "<channel>\n";
		$ret .= '<link>'.$this->url."</link>\n";
		$ret .= "<title>".$this->title."</title>\n";
		$ret .= "<description>".$this->description."</description>\n";
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

class AtomEntry extends LnBlogObject {

	var $id;
	var $title;
	var $update_time;
	var $author;
	var $content;
	var $link;
	var $summary;

	function AtomEntry($id="",$title="",$udtime="",$link="",$cont="",$author="",$sum="") {
		
	}

}

?>
