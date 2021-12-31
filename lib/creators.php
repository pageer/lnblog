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

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

# Function: NewFS
# Creates a new filesystem access object.
function NewFS() {
    if ( file_exists(Path::mk(USER_DATA_PATH, FS_PLUGIN_CONFIG)) ) {
        require_once(Path::mk(USER_DATA_PATH, FS_PLUGIN_CONFIG));
    } else {
        if (!defined("FS_PLUGIN")) define("FS_PLUGIN", "nativefs");
    }
    switch (FS_PLUGIN) {
        case "nativefs":
            return new NativeFS;
        case "ftpfs":
            return new FTPFS;
        default:
            return new NativeFS;
    }
}

# Function: NewLogger
# Create a new PSR logger object.
function NewLogger() {
    $logger = new Logger('lnblog');
    $handler = new StreamHandler(Path::mk(USER_DATA_PATH, 'application.log'));
    $logger->pushHandler($handler);
    return $logger;
}

# Function: NewBlog
# Creates a new blog object
function NewBlog($param=false) {
    return new Blog($param);
}

function NewEntry($param = false, $fs = null) {
    $entid = false;

    if (isset($_GET['article'])) {
        $entid = $_GET['article'];
    } elseif (isset($_GET['entry'])) {
        $entid = $_GET['entry'];
    } elseif (isset($_GET['draft'])) {
        $entid = $_GET['draft'];
    } else {
        if (! $param) $param = getcwd();
        if (strpos($param, BLOG_ARTICLE_PATH) !== false) {
            $entid = $param;
            if (basename($entid) == ENTRY_COMMENT_DIR)
                $entid = dirname($entid);
        } elseif (strpos($param, BLOG_ENTRY_PATH) !== false ||
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

    return new BlogEntry($entid, $fs);
}

# Function: NewBlogEntry
# Creates a new blog entry object.
function NewBlogEntry($param=false) {
    return new BlogEntry($param);
}

# Function: NewTemplate
# Creates a new template object.
function NewTemplate($tpl="", $page=null) {
    return new PHPTemplate($tpl, $page);
}

# Function: NewUser
# Creates a new user object.
#
# Parameters:
# usr - The *optional* username of the user represented by this object.
function NewUser($usr=false) {
    return User::get($usr);
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
