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
		$this->ip = get_ip();
		$this->date = "";
		$this->timestamp = "";
		$this->subject = "";
		$this->data = "";
		$this->has_html = MARKUP_BBCODE;
		$this->file = $path . ($path ? PATH_DELIM.$revision : "");
		$this->allow_comment = true;
		$this->template_file = ARTICLE_TEMPLATE;
		if ( file_exists($this->file) )$this->readFileData();
	}

	function markSticky($show=true) {
		$f = CreateFS();
		if ($show) 
			$ret = $f->write_file(dirname($this->file).PATH_DELIM.STICKY_PATH, $this->subject);
		else 
			$ret = $f->delete(dirname($this->file).PATH_DELIM.STICKY_PATH);
		$f->destruct();
		return $ret;
	}

	function getPath() {
		$path = strtolower($this->subject);
		$path = preg_replace("/\s+/", "_", $path);
		$path = preg_replace("/\W+/", "", $path);
		return $path;
	}
	
	function insert ($branch=false, $base_path=false) {
	
		if (! check_login()) return false;
	
		$curr_ts = time();
		if (!$base_path) $basepath = getcwd().PATH_DELIM.BLOG_ARTICLE_PATH;
		else $basepath = $base_path;
		if (! is_dir($basepath)) create_directory_wrappers($basepath, BLOG_ARTICLES);
		if (! $branch) $dir_path = $basepath.PATH_DELIM.$this->getPath();
		else $dir_path = $basepath.PATH_DELIM.$branch;
		$ret = create_directory_wrappers($dir_path, ARTICLE_BASE);

		$this->file = $dir_path.PATH_DELIM.ENTRY_DEFAULT_FILE;
		$this->date = date(ENTRY_DATE_FORMAT, $curr_ts);
		$this->timestamp = $curr_ts;
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

		$tmp->set("TITLE", $this->subject);
		$tmp->set("POSTDATE", $this->prettyDate() );
		$this->data = $this->markup($this->data);
		$tmp->set("BODY", $this->data);
		$tmp->set("PERMALINK", $this->permalink() );
		$tmp->set("POSTEDIT", $this->permalink()."edit.php");
		$tmp->set("POSTDELETE", $this->permalink()."delete.php");
		
		$ret = $tmp->process();
		return $ret;
	}

}
