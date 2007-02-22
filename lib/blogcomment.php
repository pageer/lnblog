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

Events:
OnInit         - Fired when object is first created.
InitComplete   - Fired at end of constructor.
OnInsert       - Fired before object is saved to persistent storage.  This is
                 run *after* the insertion setup is done, but *before* 
                 anything is actually saved to disk.
InsertComplete - Fired after object has finished saving.
OnDelete       - Fired before object is deleted.
DeleteComplete - Fired after object is deleted.
OnUpdate       - Fired before changes are saved to persistent storage.
UpdateComplete - Fired after changes to object are saved.
OnOutput       - Fired before output is generated.
OutputComplete - Fired after output has finished being generated.
POSTRetrieved  - Fired after data has been retreived from an HTTP POST.
*/
class BlogComment extends Entry {

	var $postid;
	var $name;
	var $email;
	var $url;
	var $show_email;
		
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
		$this->show_email = COMMENT_EMAIL_VIEW_PUBLIC;
		$this->exclude_fields = array('exclude_fields', 'metadata_fields',
		                              'file');
		$this->metadata_fields = array("id"=>"postid", "uid"=>"userid",
			"name"=>"name", "email"=>"e-mail", "url"=>"url",
			"show_email"=>"show_email", "date"=>"date", "post_date"=>"postdate",
			"timestamp"=>"timestamp", "post_ts"=>"posttimestamp",
			"ip"=>"ip", "subject"=>"subject");
		
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
		
	# Method: uri
	# Get the URI for various functions.

	function uri($type) {
		switch ($type) {
			case "permalink":
			case "comment":
				return localpath_to_uri(dirname($this->file))."#".$this->getAnchor();
			case "delete":
				$qs_arr = array();
				$parent = $this->getParent();
				$entry_type = is_a($this, 'Article') ? 'article' : 'entry';
				$qs_arr['blog'] = $parent->parentID();
				$qs_arr[$entry_type] = $parent->entryID();
				$qs_arr['delete'] = $this->getAnchor();
				return make_uri(INSTALL_ROOT_URL."pages/delcomment.php", $qs_arr);
				#return localpath_to_uri(dirname($this->file)).
				#       "delete.php?comment=".$this->getAnchor();
		}
	}
	
	function queryStringToID() {
		return false;
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
	}
		
	/*
	Method: update
	Commit changes to a comment.

	Returns:
	True on success, false on failure.
	*/
	function update () {
		$this->raiseEvent("OnUpdate");
		if (KEEP_COMMENT_HISTORY) $ret = $this->delete();
		else $ret = true;
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
		if (KEEP_COMMENT_HISTORY) {
			$curr_ts = time();
			$dir_path = dirname($this->file);
			if (! is_dir($dir_path.PATH_DELIM.COMMENT_DELETED_PATH) )
				$fs->mkdir_rec($dir_path.PATH_DELIM.COMMENT_DELETED_PATH);
			$source_file = $this->file;
			$target_file = basename($this->file)."-".$this->getPath($curr_ts);
			$target_file = $dir_path.PATH_DELIM.COMMENT_DELETED_PATH.
				PATH_DELIM.$target_file;
			$ret = $fs->rename($source_file, $target_file);
		} else {
			$ret = $fs->delete($this->file);
		}
		$fs->destruct();
		$this->raiseEvent("DeleteComplete");
		return $ret;
	}
	
	/* 
	Method: insert
	Add a new comment on an entry or article.

	Parameters:
	Entry - The entry to which this comment will belong.  This determines where
	        the comment is stored.

	Returns:
	True on success, false on failure.
	*/
	function insert($entry) {	
	
		$curr_ts = time();
		$usr = NewUser();
		if (!$this->uid) $this->uid = $usr->username();
	
		$basepath = mkpath($entry->localpath(), ENTRY_COMMENT_DIR);
		
		$this->file = mkpath($basepath, $this->getPath($curr_ts).COMMENT_PATH_SUFFIX);
		$this->date = fmtdate(ENTRY_DATE_FORMAT, $curr_ts);
		$this->timestamp = $curr_ts;
		$this->ip = get_ip();

		# Initial setup complete, start writing things to disk.
		$this->raiseEvent("OnInsert");
		# If there is no data for this comment, then abort.
		if (! $this->data) return false;
		if (! is_dir($basepath) ) {
			$ret = create_directory_wrappers($basepath, ENTRY_COMMENTS);
			if (! $ret) return false;
		}
		
		$ret = $this->writeFileData();
		$this->raiseEvent("InsertComplete");
		return $ret;
	}

	/*
	Method: getPostData
	Pulls data out of the HTTP POST headers and into the object.
	
	The interface for this uses pre-defined POST field names they are as
	follows.  If the poster is an authenticated user, then the userid is also
	recorded automatically from the HTTP session.
	username  - The name of the poster.
	email     - The poster's e-mail address.
	url       - The poster's homepage.
	showemail - If not empty, show the poster's e-mail address publically.
	subject   - The subject of the post.
	data      - The post content.  This cannot be empty.
	*/
	function getPostData() {
		if (! has_post()) return false;
		$this->name = POST("username");
		$this->email = POST("email");
		$this->url = POST("url");
		$this->subject = POST("subject");
		$this->data = POST("data");
		$this->show_email = POST("showemail") ? true : false;
		foreach ($this->custom_fields as $fld=>$desc) {
			$this->$fld = POST($fld);
			$this->$fld = $this->stripHTML($this->$fld);
		}
		# Note: Don't strip HTML from the comment data, because we do that 
		# when we add in the links and other markup.
		$this->name = $this->stripHTML($this->name);
		$this->email = $this->stripHTML($this->email);
		$this->url = $this->stripHTML($this->url);
		$this->subject = $this->stripHTML($this->subject);
		
		if (! $this->uid) {
			$u = NewUser();
			$this->uid = $u->username();
		}
		
		$this->raiseEvent("POSTRetreived");
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
		if (strpos($anchor, "#") !== false) {
			$pieces = split('#', $anchor);
			$entid = dirname($pieces[0]);
			$cmtid = $pieces[1];
		} else {
			$entid = false;
			$cmtid = $anchor;
		}
		$ent = NewEntry($entid);
		$ret = substr($cmtid, 7);
		$ret .= COMMENT_PATH_SUFFIX;
		$ret = mkpath($ent->localpath(),ENTRY_COMMENT_DIR,$ret);
		$ret = realpath($ret);
		return $ret;
	}

	# Method: globalID
	# Get the global identifier for this trackback.
	function globalID() {
		$parent = $this->getParent();
		$id = $parent->globalID();
		if (defined('ENTRY_COMMENT_DIR') && ENTRY_COMMENT_DIR) {
			$id .= '/'.ENTRY_COMMENT_DIR;
		}
		$id .= '/#'.$this->getAnchor();
		return $id;
	}
	
	/*
	Method: permalink
	Get the permalink to the object.  This is essentially the URI of the 
	parent's comments page with the anchor name appended.
	
	Returns:
	The full URI to the object's permalink, including page anchor.
	*/
	function permalink() {
		return $this->uri("permalink");
	}

	/*
	Method: isComment
	Determines whether or not the object is an existing, saved comment.

	Parameters:
	path - The *optional* path to the comment data file.  If not specified, 
	       then the file property is used.
	
	Returns:
	True if the comment data file exists, false otherwise.
	*/

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
		if (file_exists($this->file)) {
			return NewEntry(dirname(dirname($this->file)));
		} else {
			return NewEntry();
		}
		#return NewEntry();
		#$par_path = dirname(dirname($this->file));
		#if (strpos($par_path, BLOG_ENTRY_PATH)) {
		#	return NewBlogEntry($par_path);
		#} elseif (strpos($par_path, BLOG_ARTICLE_PATH)) {
		#	return NewArticle($par_path);
		#} else {
		#	return false;
		#}
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

		global $SYSTEM;
		
		# An array of the form label=>URL which holds the list of 
		# administrative items, such as the delete link.
		$this->control_bar = array();
		# Add the delete link.
		$this->control_bar[] = '<a href="'.$this->uri("delete").
			'" onclick="return comm_del(this,\''.$this->getAnchor()
			.'\');">'._("Delete").'</a>';

		ob_start();
		$this->raiseEvent("OnOutput");
		$ret .= ob_get_contents();
		ob_end_clean();
		
		$t = NewTemplate(COMMENT_TEMPLATE);

		$blog = NewBlog();
		$usr = NewUser();
		$show_edit_controls = $SYSTEM->canModify($this->getParent());

		if (! $this->name) $this->name = ANON_POST_NAME;
		if (! $this->subject) $this->subject = NO_SUBJECT;
		if ($this->uid) {
			$u = NewUser($this->uid);
			$u->exportVars($t);
		}
		
		$t->set("SUBJECT", $this->subject);
		if ( strtolower(substr(trim($this->url), 0, 7)) != "http://" &&
		     $this->url != "" ) {
			$this->url = "http://".$this->url;
		}
		$t->set("URL", $this->url);
		$t->set("NAME", $this->name);
		$t->set("DATE", $this->prettyDate($this->post_ts) );
		$t->set("EDITDATE", $this->prettyDate() );
		if ($this->show_email || $usr->checkLogin()) {
			$t->set("SHOW_MAIL", true);
			$t->set("EMAIL", $this->email);
		} else {
			$t->set("SHOW_MAIL", false);
			$t->set("EMAIL", "");
		}
		$t->set("PROFILE_LINK", INSTALL_ROOT_URL."userinfo.php?user=".
		                        $this->uid.
		                        "&amp;blog=".$blog->blogid);
		$t->set("ANCHOR", $this->getAnchor() );
		$t->set("SHOW_CONTROLS", $show_edit_controls);
		$t->set("BODY", $this->markup($this->data, COMMENT_NOFOLLOW) );
		$t->set("CONTROL_BAR", $this->control_bar);
		
		$ret .= $t->process();
		ob_start();
		$this->raiseEvent("OutputComplete");
		$ret .= ob_get_contents();
		ob_end_clean();
		
		return $ret;
	}
	
}

?>
