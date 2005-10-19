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

# File: constants.php
# Holds various constants used by back-end classes.

# Section: Page DOCTYPEs
# Constant: XHTML_1_1
# Full doctype for XHTML 1.1
define("XHTML_1_1", "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">");
# Constant: XHTML_1_0_Strict
# Full doctype for XHTML 1.0 Strict
define("XHTML_1_0_Strict", "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">");
# Constant: XHTML_1_0_Transitional
# Full doctype for XHTML 1.0 Transitional
define("XHTML_1_0_Transitional", "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">");
# Constant: XHTML_1_0_Frameset
# Full doctype for XHTML 1.0 Frameset
define("XHTML_1_0_Frameset", "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Frameset//EN\"\n\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd\">");
# Constant: HTML_4_01_Strict
# Full doctype for HTML 4.01 Strict
define("HTML_4_01_Strict", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\n\"http://www.w3.org/TR/html4/strict.dtd\">");
# Constant: HTML_4_01_Transitional
# Full doctype for HTML 4.01 Transitional
define("HTML_4_01_Transitional", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"\n\"http://www.w3.org/TR/html4/loose.dtd\">");
# Constant: HTML_4_01_Frameset
# Full doctype for HTML 4.01 Frameset
define("HTML_4_01_Frameset", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\"\n\"http://www.w3.org/TR/html4/frameset.dtd\">");
# Constant: HTML_3_2
# Full doctype for HTML 3.2
define("HTML_3_2", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 3.2 Final//EN\">");

# Named constants for template files.
define("BLOG_TEMPLATE_DIR", "templates");
define("BASIC_LAYOUT_TEMPLATE", "basic_layout_tpl.php");
define("PAGE_HEAD_TEMPLATE", "page_head_tpl.php");
define("ARCHIVE_TEMPLATE", "archive_tpl.php");
define("BLOG_TEMPLATE", "blog_tpl.php");
define("ENTRY_TEMPLATE", "blogentry_tpl.php");
define("ARTICLE_TEMPLATE", "article_tpl.php");
define("COMMENT_TEMPLATE", "blogcomment_tpl.php");
define("COMMENT_LIST_TEMPLATE", "comment_list_tpl.php");
define("COMMENT_FORM_TEMPLATE", "comment_form_tpl.php");
define("BLOG_EDIT_TEMPLATE", "blogedit_tpl.php");
define("ARTICLE_EDIT_TEMPLATE", "article_edit_tpl.php");
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

# Update status constants for blog entry modifying.
define("UPDATE_SUCCESS", 1);
define("UPDATE_NO_DATA_ERROR", 0);
define("UPDATE_ENTRY_ERROR", -1);
define("UPDATE_RSS1_ERROR", -2);
define("UPDATE_RSS2_ERROR", -3);
define("UPDATE_AUTH_ERROR", -4);

# Constants for handling news feeds.
# Decide whether to use permalinks as Globally Unique Identifiers.
define("GUID_IS_PERMALINK", true);

# Status constants for the file upload class.
define("FILEUPLOAD_NO_ERROR", 0);
define("FILEUPLOAD_NO_FILE", 1);
define("FILEUPLOAD_FILE_EMPTY", 2);
define("FILEUPLOAD_SERVER_TOO_BIG", 3);
define("FILEUPLOAD_FORM_TOO_BIG", 4);
define("FILEUPLOAD_PARTIAL_FILE", 5);
define("FILEUPLOAD_NOT_UPLOADED", 6);
define("FILEUPLOAD_NAME_TRUNCATED", 7);
define("FILEUPLOAD_BAD_NAME", 8);
define("FILEUPLOAD_NOT_INITIALIZED", 9);

# Constants used for user login sessions and cookies.
define("LOGIN_TOKEN", "lToken");
define("LAST_LOGIN_TIME", "lastLTime");
define("CURRENT_USER", "uName");
define("PW_HASH", "uHash");

?>
