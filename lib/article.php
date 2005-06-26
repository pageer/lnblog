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
require_once("blogentry.php");

class Article extends BlogEntry {

	function Article($path="", $revision=ENTRY_DEFAULT_FILE) {
		$this->uid = ADMIN_USER;
		$this->ip = get_ip();
		$this->date = "";
		$this->timestamp = "";
		$this->subject = "";
		$this->data = "";
		$this->has_html = MARKUP_BBCODE;
		$this->file = $path . ($path ? PATH_DELIM.$revision : "");
		$this->allow_comment = true;
		$this->template_file = ARTICLE_TEMPLATE;
		if ( file_exists($this->file) ) {
			$this->readFileData();
		}
	}

	function setSticky($show=true) {
		$f = CreateFS();
		if ($show) 
			$ret = $f->write_file(dirname($this->file).PATH_DELIM.STICKY_PATH, $this->subject);
		else 
			$ret = $f->delete(dirname($this->file).PATH_DELIM.STICKY_PATH);
		$f->destruct();
		return $ret;
	}

	function getPath($curr_ts=false, $just_name=false, $long_format=false) {
		if (! $curr_ts) {
			$path = strtolower($this->subject);
			$path = preg_replace("/\s+/", "_", $path);
			$path = preg_replace("/\W+/", "", $path);
			return $path;
		} else {
			$year = date("Y", $curr_ts);
			$month = date("m", $curr_ts);
			$fmt = $long_format ? ENTRY_PATH_FORMAT_LONG : ENTRY_PATH_FORMAT;
			$base = date($fmt, $curr_ts);
			if ($just_name) return $base;
			else return $year.PATH_DELIM.$month.PATH_DELIM.$base;
		}
	}

	function insert ($branch=false, $base_path=false) {
	
		$usr = new User();
		if (! $usr->checkLogin()) return false;
		$this->uid = $usr->username();
	
		$curr_ts = time();
		if (!$base_path) $basepath = getcwd().PATH_DELIM.BLOG_ARTICLE_PATH;
		else $basepath = $base_path;
		if (! is_dir($basepath)) create_directory_wrappers($basepath, BLOG_ARTICLES);
		if (! $branch) $dir_path = $basepath.PATH_DELIM.$this->getPath();
		else $dir_path = $basepath.PATH_DELIM.$branch;
		$ret = create_directory_wrappers($dir_path, ARTICLE_BASE);

		$this->file = $dir_path.PATH_DELIM.ENTRY_DEFAULT_FILE;
		$this->date = date(ENTRY_DATE_FORMAT, $curr_ts);
		if (! $this->post_date) $this->post_date = date(ENTRY_DATE_FORMAT, $curr_ts);
		$this->timestamp = $curr_ts;
		if (! $this->post_ts) $this->post_ts = $curr_ts;
		$this->ip = get_ip();
		
		if (get_magic_quotes_gpc() ) {
			$this->subject = stripslashes($this->subject);
			$this->data = stripslashes($this->data);
		}

		$ret = $this->writeFileData();
		return $ret;
		
	}
	
	function get() {
		$tmp = new PHPTemplate(ARTICLE_TEMPLATE);

		$usr = new User($this->uid);
		$usr->exportVars($tmp);

		$tmp->set("TITLE", $this->subject);
		$tmp->set("POSTDATE", $this->prettyDate($this->post_ts) );
		$tmp->set("EDITDATE", $this->prettyDate() );
		$body_text = $this->data;
		if ($this->has_html == MARKUP_BBCODE) 
			$body_text = $this->absolutizeBBCodeURI($body_text, $this->permalink() );
		$body_text = $this->markup($body_text);
		$tmp->set("BODY", $body_text);
		$tmp->set("PERMALINK", $this->permalink() );
		$tmp->set("POSTEDIT", $this->permalink()."edit.php");
		$tmp->set("POSTDELETE", $this->permalink()."delete.php");
		
		$ret = $tmp->process();
		return $ret;
	}

}
