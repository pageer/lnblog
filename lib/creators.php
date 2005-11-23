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

require_once("blogconfig.php");

# Function: NewFS
# Creates a new filesystem access object.
function NewFS() {
	if ( file_exists(USER_DATA_PATH.PATH_DELIM.FS_PLUGIN_CONFIG) ) {
		require_once(USER_DATA.PATH_DELIM.FS_PLUGIN_CONFIG);
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
	require_once("page.php");
	return new Page($ref);
}
# Function: NewBlog
# Creates a new blog object
function NewBlog($param=false) {
	require_once("blog.php");
	return new Blog($param);
}

# Function: NewBlogEntry
# Creates a new blog entry object.
function NewBlogEntry($param=false) {
	require_once("blogentry.php");
	return new BlogEntry($param);
}

# Function: NewArticle
# Creates a new article object
function NewArticle($param=false) {
	require_once("article.php");
	return new Article($param);
}

# Function: NewBlogComment
# Creates a new BlogComment on an entry or article.
function NewBlogComment($param=false) {
	require_once("blogcomment.php");
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
	require_once("user.php");
	return new User($usr, $pwd);
}

# Function: NewFileUpload
# Creates a new file uploader object.
#
# Parameters:
# field - The form field that this object represents.
# dir   - The *optional* target directory.
# index - The *optional* index of this upload for file upload arrays.
function NewFileUpload($field, $dir=false, $index=false) {
	require_once("upload.php");
	return new FileUpload($field, $dir=false, $index=false);
}

# Function: NewTrackback
# Creates a new trackback object.
function NewTrackback($param=false) {
	require_once("tb.php");
	return new Trackback($param);
}

# Function: NewIniParser
# Creates a new INI file parser object.
function NewIniParser($file=false) {
	require_once("iniparser.php");
	return new INIParser($file);
}

?>