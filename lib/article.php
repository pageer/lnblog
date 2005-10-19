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
require_once("lib/creators.php");
require_once("lib/blogentry.php");

/*
Class: Article
Represents a static article.  

Inherits:
<LNBlogObject>, <Entry>, <BlogEntry>

Events:
OnInit       - Fired when the object is created.
InitComplete

*/

class Article extends BlogEntry {

	function Article($path="", $revision=ENTRY_DEFAULT_FILE) {
		$this->raiseEvent("OnInit");
		$this->uid = ADMIN_USER;
		$this->ip = get_ip();
		$this->date = "";
		$this->timestamp = "";
		$this->subject = "";
		$this->data = "";
		$this->has_html = MARKUP_BBCODE;
		$this->allow_comment = true;
		$this->template_file = ARTICLE_TEMPLATE;

		if (! $path) {
			$this->file = getcwd().PATH_DELIM.$revision;
			# We might be in a comment or trackback directory, 
			if (! $this->isEntry() ) {
				$this->file = dirname(getcwd()).PATH_DELIM.$revision;
				if (! $this->isEntry() ) {
					$this->file = getcwd().PATH_DELIM.$revision;
				}
			}
		} else {
			$this->file = $path.PATH_DELIM.$revision;
		}

		if ( file_exists($this->file) ) {
			$this->readFileData();
		}
		$this->raiseEvent("InitComplete");
	}

	/*
	Method: isArticle
	Determine if this object is, in fact, an article.  This is based on the
	internal storage format of the article information, i.e. if the correct
	storage format is found, then it's an article.

	Parameters:
	path - *Optional* parameter for the unique ID of the object.  This should
	       only be used by back-end classes.
	
	Returns:
	True if object is a valid article, false otherwise.
	*/

	function isArticle($path=false) {
		return $this->isEntry($path);
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

	function setSticky($show=true) {
		$f = NewFS();
		if ($show) 
			$ret = $f->write_file(dirname($this->file).PATH_DELIM.STICKY_PATH, $this->subject);
		else 
			$ret = $f->delete(dirname($this->file).PATH_DELIM.STICKY_PATH);
		$f->destruct();
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
	

	function isSticky($path=false) {
		return ($this->isArticle($path) && 
		        file_exists($path ? $path : 
				              (dirname($this->file).PATH_DELIM.STICKY_PATH) ) );
	}

	/*
	Method: readSticky
	Get the title and permalink without retreiving the entire article.
	
	Parameters:
	path - The unique ID for the article.
	
	Returns:
	A two-element array, with "link" and "title" for the permalink and
	subject of the article.
	*/

	function readSticky($path) {
	
		$old_path = $this->file;
		if (is_dir($path)) $this->file = $path.PATH_DELIM.ENTRY_DEFAULT_FILE;
		else $this->file = $path;
		$sticky_file = dirname($this->file).PATH_DELIM.STICKY_PATH;
		
		if ( file_exists($sticky_file) ) {
			$data = file($sticky_file);
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
	Method: getPath
	Builds a path to store the given article.  This is only meaningful for
	file-based storage and should only be used internally.

	Parameters:
	curr_ts     - An *optional* timestamp.
	just_name   - *Optional* boolean to return just a file name.
	long_format - *Optional* boolean to use long date format.

	Returns:
	A string to use for the path to the article.
	*/

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

	/*
	Method: insert
	Save the object as a new article.

	Parameters:
	branch    - *Optional* boolean for whether to create a new branch of an
	            existing article.
	base_path - *Optional* string to use for the base path to articles.

	Returns:
	True on success, false on failure.
	*/

	function insert ($branch=false, $base_path=false) {
	
		$usr = NewUser();
		if (! $usr->checkLogin()) return false;
		$this->raiseEvent("OnInsert");
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

		$ret = $this->writeFileData();
		$this->raiseEvent("InsertComplete");
		return $ret;
		
	}
	
	/*
	Method: get
	Get the HTML code to display the article.

	Parameters:
	show_edit_controls - *Optional* boolean for whether or not to show the 
	                     links for modifying the article.  Default is false.

	Returns:
	A string containing the HTML to dump to the browser for this article.
	*/
	
	function get($show_edit_controls=false) {
		ob_start();
		$this->raiseEvent("OnOutput");
		$ret= ob_get_contents();
		ob_end_clean();
		$tmp = NewTemplate(ARTICLE_TEMPLATE);

		$usr = NewUser($this->uid);
		$usr->exportVars($tmp);

		$tmp->set("TITLE", $this->subject);
		$tmp->set("POSTDATE", $this->prettyDate($this->post_ts) );
		$tmp->set("EDITDATE", $this->prettyDate() );
		$tmp->set("BODY", $this->markup() );
		$tmp->set("PERMALINK", $this->permalink() );
		$tmp->set("SHOW_CONTROLS", $show_edit_controls);
		
		$ret .= $tmp->process();
		ob_start();
		$this->raiseEvent("OutputComplete");
		$ret .= ob_get_contents();
		ob_end_clean();
		return $ret;
	}

}
