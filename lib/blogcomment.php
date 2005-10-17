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
require_once("lib/creators.php");
require_once("lib/entry.php");

/*
Class: BlogComment
Represents a comment on a blog entry or article.

Inherits:
<LnBlogObject>, <Entry>
*/
class BlogComment extends Entry {

	var $postid;
	var $name;
	var $email;
	var $url;
		
	function BlogComment ($path="", $revision="") {
		$this->raiseEvent("OnInit");
		$this->ip = get_ip();
		$this->date = "";
		$this->timestamp = 0;
		$this->subject = "";
		$this->data = "";
		$this->file = $path . ($revision ? "_".$revision : "");
		$this->url = "";
		$this->email = "";
		$this->name = ANON_POST_NAME;
		$this->has_html = MARKUP_NONE;
		if ( file_exists($this->file) ) {
			$this->readFileData();
		} elseif ( file_exists( $this->getFilename($path) ) ) {
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
	function getPath($ts) {
		$base = date(COMMENT_PATH_FORMAT, $ts);
		return $base;
	}
		
	function metadataFields() {
		$ret = array();
		$ret["PostID"] =  $this->id;
		$ret["UserID"] =  $this->uid;
		$ret["Name"] =  $this->name;
		$ret["E-Mail"] =  $this->email;
		$ret["URL"] = $this->url;
		$ret["Date"] =  $this->date;
		$ret["PostDate"] = $this->post_date;
		$ret["Timestamp"] =  $this->timestamp;
		$ret["PostTimestamp"] = $this->post_ts;
		$ret["Timestamp"] =  $this->timestamp;
		$ret["IP"] =  $this->ip;
		$ret["Subject"] =  $this->subject;
		return $ret;
	}

	function addMetadata($key, $val) {
		switch ($key) {
			case "PostID": $this->id = $val; break;
			case "UserID": $this->uid = $val; break;
			case "Name": $this->name = $val; break;
			case "E-Mail": $this->email = $val; break;
			case "URL": $this->url = $val; break;
			case "Date": $this->date = $val; break;
			case "PostDate": $this->post_date = $val; break;
			case "Timestamp": $this->timestamp = $val; break;
			case "PostTimestamp": $this->post_ts = $val; break;
			case "Timestamp": $this->timestamp = $val; break;
			case "IP": $this->ip = $val; break;
			case "Subject": $this->subject = $val; break;
		}
		#echo "<p>Meta: $key, $val</p>";
	}
		
	/*
	Method: update
	Commit changes to a comment.

	Returns:
	True on success, false on failure.
	*/
	function update () {
		$this->raiseEvent("OnUpdate");
		$ret = $this->delete();
		if ($ret) {
			$base_path = dirname($this->file); 
			$ret = $this->insert($base_path);
		}
		$this->raiseEvent("UpdateComplete");
		return $ret;
	}
	
	/* Method: delete
	Delete a comment.

	Returns:
	True on success, false on failure.
	*/
	function delete () {
		$this->raiseEvent("OnDelete");
		$fs = NewFS();
		$curr_ts = time();
		$dir_path = dirname($this->file);
		if (! is_dir($dir_path.PATH_DELIM.COMMENT_DELETED_PATH) )
			$fs->mkdir_rec($dir_path.PATH_DELIM.COMMENT_DELETED_PATH);
		$source_file = $this->file;
		$target_file = basename($this->file)."-".$this->getPath($curr_ts);
		$target_file = $dir_path.PATH_DELIM.COMMENT_DELETED_PATH.
			PATH_DELIM.$target_file;
		$ret = $fs->rename($source_file, $target_file);
		$fs->destruct();
		$this->raiseEvent("DeleteComplete");
		return $ret;
	}
	
	/* 
	Method: insert
	Add a new comment.  Note that this should normally be called internally
	by a BlogEntry or Article object, as a comment is logically a child of 
	an entry (or, by inheritance, an article).

	Parameters:
	basepath - The storage identifier (e.g. directory) for comments belonging
	           to the parent object.

	Returns:
	True on success, false on failure.
	*/
	function insert($basepath) {
	
		$this->raiseEvent("OnInsert");
	
		$curr_ts = time();
		$usr = NewUser();
		if ($usr->checkLogin()) $this->uid = $usr->username();
	
		# Check if the file path is NULL so that we can re-use this routine
		# when updating an entry.
		if (! $this->file)
			$this->file = $basepath.PATH_DELIM.$this->getPath($curr_ts).COMMENT_PATH_SUFFIX;
		$this->date = date(ENTRY_DATE_FORMAT, $curr_ts);
		$this->timestamp = $curr_ts;
		$this->ip = get_ip();

		if (! is_dir($basepath) ) {
			$ret = create_directory_wrappers($basepath, ENTRY_COMMENTS);
			if (! $ret) return false;
		}
		
		# If there is no data for this comment, then abort.
		if (! $this->data) return false;

		$ret = $this->writeFileData();
		$this->raiseEvent("InsertComplete");
		return $ret;
	}

	/*
	Method: getPostData
	Pulls data out of the HTTP POST headers and into the object.
	*/
	function getPostData() {
		$this->name = POST(COMMENT_POST_NAME);
		$this->email = POST(COMMENT_POST_EMAIL);
		$this->url = POST(COMMENT_POST_URL);
		$this->subject = POST(COMMENT_POST_SUBJECT);
		$this->data = POST(COMMENT_POST_DATA);
		if (get_magic_quotes_gpc()) {
			$this->name = stripslashes($this->name);
			$this->email = stripslashes($this->email);
			$this->url = stripslashes($this->url);
			$this->subject = stripslashes($this->subject);
			$this->data = stripslashes($this->data);
		}
		$this->name = $this->stripHTML($this->name);
		$this->email = $this->stripHTML($this->email);
		$this->url = $this->stripHTML($this->url);
		$this->subject = $this->stripHTML($this->subject);
		# Don't strip HTML from the comment data, because we do that 
		# when we add in the links and other markup.
	}

	/*
	Method: getAnchor
	Get text to use as the name attribute in an HTML anchor tag.

	Returns:
	A string for anchor text based on the file name/storage ID.
	*/
	function getAnchor() {
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
	function getFilename($anchor) {
		$ret = substr($anchor, 7);
		$ret .= COMMENT_PATH_SUFFIX;
		$ret = realpath($ret);
		return $ret;
	}

	/*
	Method: permalink
	Get the permalink to the object.  This is essentially the URI of the 
	parent's comments page with the anchor name appended.
	
	Returns:
	The full URI to the object's permalink, including page anchor.
	*/
	function permalink() {
		return localpath_to_uri(dirname($this->file))."#".$this->getAnchor();
	}

	function isComment($path=false) {
		if (!$path) $path = $this->file;
		return file_exists($path);
	}

	/*
	Method: getParent
	Gets a copy of the parent object of this comment, i.e. the object which
	this is a comment on.
	
	Returns:
	A BlogEntry or Article object, depending on the context.
	*/
	function getParent() {
		if (! file_exists($this->file)) return NewBlogEntry();
		$par_path = dirname(dirname($this->file));
		if (substr($par_path, BLOG_ENTRY_PATH)) {
			return NewBlogEntry($par_path);
		} elseif (substr($par_path, BLOG_ARTICLE_PATH)) {
			return NewArticle($par_path);
		} else {
			return false;
		}
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
	function get($show_edit_controls=false) {
		$t = NewTemplate(COMMENT_TEMPLATE);

		$blog = NewBlog();
		$usr = NewUser();
		$show_edit_controls = $blog->canModifyEntry();

		#$t->set("ID", $this->id);
		if (! $this->name) $this->name = ANON_POST_NAME;
		if (! $this->subject) $this->subject = NO_SUBJECT;
		if ($this->uid) {
			$u = NewUser($this->uid);
			$u->exportVars($t);
		}
		
		$t->set("SUBJECT", $this->subject);
		$t->set("URL", $this->url);
		$t->set("NAME", $this->name);
		$t->set("DATE", $this->prettyDate($this->post_ts) );
		$t->set("EDITDATE", $this->prettyDate() );
		$t->set("EMAIL", $this->email);
		if (COMMENT_EMAIL_VIEW_PUBLIC || $usr->checkLogin()) {
			$t->set("SHOW_MAIL", true);
		} else {
			$t->set("SHOW_MAIL", false);
		}
		$t->set("ANCHOR", $this->getAnchor() );
		$t->set("SHOW_CONTROLS", $show_edit_controls);
		$t->set("BODY", $this->markup($this->data, COMMENT_NOFOLLOW) );
		
		$ret = $t->process();
		return $ret;
	}
	
}

?>
