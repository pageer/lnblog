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

# File: creators.php
# These are a collection of functions that return an object of the 
# appropriate type based on certain parameters.  
# The purpose of this is to allow for a plugin-like configuration mechanism.
# The idea is that we can use these functions to automagically determine the
# system configuration and do things like swap between implementations, e.g. 
# the NativeFS vs. FTPFS problem.  Because these functions take care of the
# detection (possibly including reading query strings?) and include the
# class files, much of the "include() hell" I've been experiencing will 
# now go away.
#
# Note that this should really be a factory class, but PHP 4 doesn't allow 
# static methods, so it's more convenient just to make them functions.

# Function: NewFS
# Creates a new filesystem access object.
function NewFS() {
	if ( file_exists(mkpath(USER_DATA_PATH, FS_PLUGIN_CONFIG)) ) {
		require_once(mkpath(USER_DATA_PATH, FS_PLUGIN_CONFIG));
	} else {
		if (!defined("FS_PLUGIN")) define("FS_PLUGIN", "nativefs");
	}
	require_once(FS_PLUGIN.".php");
	switch (FS_PLUGIN) {
		case "nativefs":
			return new NativeFS;
		case "ftpfs":
			return new FTPFS;
		default:
			return new NativeFS;
	}
}

# Function: NewPage
# Creates a new page object.
function NewPage($ref=false) {
	return new Page($ref);
}
# Function: NewBlog
# Creates a new blog object
function NewBlog($param=false) {
	return new Blog($param);
}

function NewEntry($param=false) {
	$artid = false;
	$entid = false;
	
	if (isset($_GET['article'])) {
		$artid = $_GET['article'];
	} elseif (isset($_GET['entry'])) {
		$entid = $_GET['entry'];
	} elseif (isset($_GET['draft'])) {
		$entid = $_GET['draft'];
	} else {
		if (! $param) $param = getcwd();
		if (strpos($param, BLOG_ARTICLE_PATH) !== false) {
			$artid = $param;
			if (basename($artid) == ENTRY_COMMENT_DIR) 
				$artid = dirname($artid);
		}
		elseif (strpos($param, BLOG_ENTRY_PATH) !== false ||
		        strpos($param, BLOG_DRAFT_PATH) !== false) {
			$entid = $param;
			$dir_base = basename($entid);
			$child_paths = array(ENTRY_COMMENT_DIR, 
			                     ENTRY_TRACKBACK_DIR, 
			                     ENTRY_PINGBACK_DIR);
			if ( in_array($dir_base, $child_paths) ) {
				$entid = dirname($entid);
			}
		}
	}
	
	if ($entid) {
		return new BlogEntry($entid);
	} elseif ($artid) {
		return new Article($artid);
	}
	return false;
}

# Function: NewBlogEntry
# Creates a new blog entry object.
function NewBlogEntry($param=false) {
	return new BlogEntry($param);
}

# Function: NewArticle
# Creates a new article object
function NewArticle($param=false) {
	return new Article($param);
}

# Function: NewBlogComment
# Creates a new BlogComment on an entry or article.
function NewBlogComment($param=false) {
	return new BlogComment($param);
}

# Function: NewTemplate
# Creates a new template object.
function NewTemplate($tpl="") {
	require_once("template.php");
	return new PHPTemplate($tpl);
}

# Function: NewUser
# Creates a new user object.
#
# Parameters:
# usr - The *optional* username of the user represented by this object.
# pwd - The *optional* associated password.
function NewUser($usr=false, $pwd=false) {
	$s_usr = SESSION(CURRENT_USER);
	$c_usr = COOKIE(CURRENT_USER);
	if (!$usr && $c_usr) {
		if ($s_usr == $c_usr || (! AUTH_USE_SESSION && $s_usr == '') ) {
			$usr = $c_usr;
		}
	}
	if ($usr && isset($_SESSION["user-".$usr])) {
		return unserialize($_SESSION["user-".$usr]);
	} else {
		return new User($usr, $pwd);
	}
}

# Function: NewFileUpload
# Creates a new file uploader object.
#
# Parameters:
# field - The form field that this object represents.
# dir   - Thfile:///home/Tallgeese/pageer/www/LnBlog/lib/creators.phpe *optional* target directory.
# index - The *optional* index of this upload for file upload arrays.
function NewFileUpload($field, $dir=false, $index=false) {
	require_once("upload.php");
	return new FileUpload($field, $dir, $index);
}

# Function: NewTrackback
# Creates a new trackback object.
function NewTrackback($param=false) {
	require_once("tb.php");
	return new Trackback($param);
}

# Function: NewPingback
# Creates a new pingback object.
function NewPingback($param=false) {
	require_once("pb.php");
	return new Pingback($param);
}

# Function: NewIniParser
# Creates a new INI file parser object.
function NewIniParser($file=false) {
	return new INIParser($file);
}

# Function: NewConfigFile
# Creates an XML config file parser object.
function NewConfigFile($file=false) {
	return new XMLINI($file);
}

# Function: NewReply
# Creates a new object based on a reply identifier.  The object is a 
# BlogComment, Trackback, or Pinback.  The type of object is based on the 
# parameter passed, which can be either an anchor name as displayed on the page 
# or a local file path.
function NewReply($id) {
	if (strpos($id, '#comment') !== false) {
		return NewBlogComment($id);
	} elseif (strpos($id, '#trackback') !== false) {
		return NewTrackback($id);
	} elseif (strpos($id, '#pingback') !== false) {
		return NewPingback($id);
	}
}
