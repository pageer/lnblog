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
Class: Article
Represents a static article.  

Inherits:
<LnBlogObject>, <Entry>, <BlogEntry>

Events:
OnInit         - Fired when the object is created.
InitComplete   - Fired at end of constructor.
OnInsert       - Fired before object is saved to persistent storage.
InsertComplete - Fired after object has finished saving.
OnOutput       - Fired before output is generated.
OutputComplete - Fired after output has finished being generated.
*/

class Article extends BlogEntry {
	
	protected $article_path = '';

	public function __construct($path="", $revision=ENTRY_DEFAULT_FILE) {
		$this->raiseEvent("OnInit");
		
		$this->initVars();
		$this->allow_comment = false;
		$this->allow_tb = false;
		$this->allow_pingback = false;
		$this->template_file = ARTICLE_TEMPLATE;
		$this->getFile($path, $revision, 'article', BLOG_ARTICLE_PATH, '/.*/');

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

	public function isArticle($path=false) {
		if (!$path) $path = dirname($this->file);
		return $this->isEntry($path) && strpos($path, BLOG_ARTICLE_PATH) !== false;
	}

	/*
	Method: setticky
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
		$f = NewFS();
		
		if ($show) 
			$ret = $f->write_file(dirname($this->file).PATH_DELIM.STICKY_PATH, $this->subject);
		elseif (file_exists(dirname($this->file).PATH_DELIM.STICKY_PATH))
			$ret = $f->delete(dirname($this->file).PATH_DELIM.STICKY_PATH);
		else $ret = true;
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

	public function readSticky($path) {
	
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
	Method: entryID
	Gets the ID of the article, which is normally just the last portion 
	of the path.
	
	Returns:
	A string containing the unique ID of this article.
	*/
	public function entryID() {
		return basename(dirname($this->file));
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

	public function getPath($curr_ts=false, $just_name=false, $long_format=false) {
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
	
	public function setPath($path) {
		$this->article_path = $path;
	}

	/*
	Method: insert
	Save the object as a new article.

	Parameters:
	blog       - The blog object into which the article should be inserted.
	from_draft - Indicates that the article is based on a draft entry, not
	             taken directly from user input.
	
	Returns:
	True on success, false on failure.
	*/

	public function insert ($blog, $from_draft=false) {

		$this->raiseEvent("OnInsert");
		if (!$this->uid) {
			$usr = NewUser();
			$this->uid = $usr->username();
		}
	
		$curr_ts = time();
		$basepath = $blog->home_path.PATH_DELIM.BLOG_ARTICLE_PATH;
		$dir_path = $this->article_path;
		
		if (! is_dir($basepath)) create_directory_wrappers($basepath, BLOG_ARTICLES);
		if (! $dir_path) $dir_path = $this->getPath();
		$dir_path = $basepath.PATH_DELIM.$dir_path;
		if ($from_draft) {
			$fs = NewFS();
			$fs->rename(dirname($this->file), $dir_path);
		}
		$ret = create_directory_wrappers($dir_path, ARTICLE_BASE);

		# Create directories for comments and trackbacks.
		if ($this->allow_comment) {
			create_directory_wrappers($dir_path.PATH_DELIM.ENTRY_COMMENT_DIR, ENTRY_COMMENTS);
		}
		if ($this->allow_tb) {
			create_directory_wrappers($dir_path.PATH_DELIM.ENTRY_TRACKBACK_DIR, ENTRY_TRACKBACKS);
		}

		$this->file = $dir_path.PATH_DELIM.ENTRY_DEFAULT_FILE;
		$this->date = fmtdate(ENTRY_DATE_FORMAT, $curr_ts);
		$this->timestamp = $curr_ts;
		if (! $this->post_ts) $this->post_ts = $curr_ts;
		$this->ip = get_ip();

		$ret = $this->writeFileData();
		if ($ret) {
			$this->id = str_replace(PATH_DELIM, '/', 
			                        substr(dirname($this->file), 
									        strlen(DOCUMENT_ROOT)));
		}
		$this->raiseEvent("InsertComplete");
		return $ret;
		
	}
	
	# This is to allow inheritance of update and delete methods without 
	# creating wrapper scripts.
	public function calcPrettyPermalink($use_broken_regex=false) { return false; }

	public function permalink($html_escape=true) {
		if (! USE_WRAPPER_SCRIPTS && 
		    ! file_exists(BLOG_ROOT.PATH_DELIM.".htaccess") ) {
			$path = localpath_to_uri(INSTALL_ROOT);
			$path .= "pages/showarticle.php?";
			$path .= "blog=".basename(dirname(dirname($this->file)));
			$path .= $html_escape ? "&amp;" : "&";
			$path .= "article=".basename(dirname($this->file));
			return $path;
		} else {
			return localpath_to_uri($this->localpath());
		}
	}

}
