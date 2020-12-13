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

# File: constants.php
# Holds various constants used by back-end classes.

# Named constants for template files.  Do I really need constants for these?
define("BLOG_TEMPLATE_DIR", "templates");
define("LIST_TEMPLATE", "list_tpl.php");
define("BASIC_LAYOUT_TEMPLATE", "basic_layout_tpl.php");
define("PAGE_HEAD_TEMPLATE", "page_head_tpl.php");
define("ENTRY_TEMPLATE", "blogentry_tpl.php");
define("ARTICLE_TEMPLATE", "article_tpl.php");
define("COMMENT_TEMPLATE", "blogcomment_tpl.php");
define("COMMENT_LIST_TEMPLATE", "comment_list_tpl.php");
define("COMMENT_FORM_TEMPLATE", "comment_form_tpl.php");
define("ENTRY_EDIT_TEMPLATE", "entry_edit_tpl.php");
define("LOGIN_TEMPLATE", "login_tpl.php");
define("CREATE_LOGIN_TEMPLATE", "login_create_tpl.php");
define("CONFIRM_TEMPLATE", "confirm_tpl.php");
define("BLOG_UPDATE_TEMPLATE", "blog_modify_tpl.php");
define("BLOG_ADMIN_TEMPLATE", "blog_admin_tpl.php");
define("UPLOAD_TEMPLATE", "upload_form_tpl.php");
define("FS_CONFIG_TEMPLATE", "fs_config_tpl.php");
define("TRACKBACK_PING_TEMPLATE", "send_trackback_tpl.php");
define("TRACKBACK_TEMPLATE", "trackback_tpl.php");
define("TRACKBACK_LIST_TEMPLATE", "trackback_list_tpl.php");
define("SITEMAP_TEMPLATE", "sitemap_config_tpl.php");
define("USER_INFO", "user_info_tpl.php");
define("FTPROOT_TEST_TEMPLATE", "ftproot_test_tpl.php");
define("DOCROOT_TEST_TEMPLATE", "docroot_test_tpl.php");
define("PLUGIN_LOAD_TEMPLATE", "plugin_load_config_tpl.php");

# Section: Markup Types
# Define types of markup available for entries. 

# Constant: MARKUP_NONE
# Use auto-markup.  All HTML tags are stripped, line and paragraph breaks 
# are auto-inserted, and URLs are auto-converted into links.
define("MARKUP_NONE", 0);
# Constant: MARKUP_BBCODE
# Use LBCode markup, a bastardized version of BBCode.
define("MARKUP_BBCODE", 1);
# Constant: MARKUP_HTML
# Treat the markup as raw HTML, leaving it untouched.
define("MARKUP_HTML", 2);
# Constant: MARKUP_MARKDOWN
# Treat the markup as Markdown markup.
define("MARKUP_MARKDOWN", 3);

# Section: BlogEntry Error Codes
# Update status constants for blog entry modifying.

# Constant: UPDATE_SUCCESS
# Update succeeded.
define("UPDATE_SUCCESS", 1);
define("UPDATE_RSS1_ERROR", -2);

# Section: FileUpload Error Codes
# Status constants used for file uploading.

# Constant: FILEUPLOAD_NO_ERROR
# Upload succeeded without errors.
define("FILEUPLOAD_NO_ERROR", 0);
# Constant: FILEUPLOAD_NO_FILE
# There was no file uploaded.
define("FILEUPLOAD_NO_FILE", 1);
# Constant:FILEUPLOAD_FILE_EMPTY
# The file was created on the server, but it is empty.
define("FILEUPLOAD_FILE_EMPTY", 2);
# Constant:	FILEUPLOAD_SERVER_TOO_BIG
# The uploaded file is too big for the server.
define("FILEUPLOAD_SERVER_TOO_BIG", 3);
# Constant: FILEUPLOAD_FORM_TOO_BIG
# The file is too big for the suggested size in the form.
define("FILEUPLOAD_FORM_TOO_BIG", 4);
# Constant: FILEUPLOAD_PARTIAL_FILE
# The file was not completely received.
define("FILEUPLOAD_PARTIAL_FILE", 5);
# Constant: FILEUPLOAD_NOT_UPLOADED
# Generic upload failure.
define("FILEUPLOAD_NOT_UPLOADED", 6);
# Constant: FILEUPLOAD_NAME_TRUNCATED
# The file name was too long and has been truncated.
define("FILEUPLOAD_NAME_TRUNCATED", 7);
# Constant: FILEUPLOAD_BAD_NAME
# The file name is not valid for upload.
define("FILEUPLOAD_BAD_NAME", 8);
# Constant: FILEUPLOAD_NOT_INITIALIZED
# Invalid form field name.
define("FILEUPLOAD_NOT_INITIALIZED", 9);

# Constants used for user login sessions and cookies.
define("LOGIN_TOKEN", "lToken");
define("LAST_LOGIN_TIME", "lastLTime");
define("CURRENT_USER", "uName");
define("PW_HASH", "uHash");
