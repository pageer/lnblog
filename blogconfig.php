<?php

# Information on the package itself.
define("PACKAGE_NAME", "LnBlog");
define("PACKAGE_VERSION", "0.1.0");
define("PACKAGE_URL", "http://www.skepticats.com/lnblog/");
define("PACKAGE_DESCRIPTION", PACKAGE_NAME.": the natural web logarithm");
define("PACKAGE_COPYRIGHT", "Copyright (c) 2005, Peter A. Geer <pageer@skepticats.com>");

# Blog configuration
define("BLOG_CONFIG_PATH", "blogdata.txt");
define("BLOG_DELETED_PATH", "deleted");
define("BLOG_ENTRY_PATH", "entries");
define("BLOG_ARTICLE_PATH", "content");
define("BLOG_FEED_PATH", "feeds");
define("BLOG_RSS1_NAME", "news.rdf");
define("BLOG_RSS2_NAME", "news.xml");
define("BLOG_MAX_ENTRIES", 10);

# Blog entry configuration
define("ENTRY_PATH_FORMAT", "d_Hi");
define("ENTRY_DEFAULT_FILE", "current.htm");
define("ENTRY_DATE_FORMAT", "Y-m-d H:i T");
define("ENTRY_COMMENT_DIR", "comments");
define("ENTRY_POST_SUBJECT", "subject");
define("ENTRY_POST_ABSTRACT", "abstract");
define("ENTRY_POST_COMMENTS", "allow_comments");
define("ENTRY_POST_DATA", "data");
define("ENTRY_POST_HTML", "usehtml");
define("ENTRY_PATH_SUFFIX", ".htm");

# Comment configuration
define("ANON_POST_NAME", "Anonymous Reader");
define("NO_SUBJECT", "No Subject");
define("COMMENT_PATH_FORMAT", "Y-m-d_His");
define("COMMENT_PATH_SUFFIX", ".txt");
define("COMMENT_DELETED_PATH", "deleted");
define("COMMENT_POST_SUBJECT", "subject");
define("COMMENT_POST_NAME", "username");
define("COMMENT_POST_EMAIL", "e-mail");
define("COMMENT_POST_URL", "url");
define("COMMENT_POST_DATA", "data");
define("COMMENT_POST_REMEMBER", "remember");

# Template file data.
define("BLOG_TEMPLATE_DIR", "templates");
define("BASIC_LAYOUT_TEMPLATE", "basic_layout_tpl.php");
define("ARCHIVE_TEMPLATE", "archive_tpl.php");
define("BLOG_TEMPLATE", "blog_tpl.php");
define("ENTRY_TEMPLATE", "blogentry_tpl.php");
define("ARTICLE_TEMPLATE", "article_tpl.php");
define("COMMENT_TEMPLATE", "blogcomment_tpl.php");
define("COMMENT_LIST_TEMPLATE", "comment_list_tpl.php");
define("COMMENT_FORM_TEMPLATE", "comment_form_tpl.php");
define("BLOG_EDIT_TEMPLATE", "blogedit_tpl.php");
define("LOGIN_TEMPLATE", "login_tpl.php");
define("CREATE_LOGIN_TEMPLATE", "login_create_tpl.php");
define("CONFIRM_TEMPLATE", "confirm_tpl.php");
define("BLOG_UPDATE_TEMPLATE", "blog_modify_tpl.php");
define("BLOG_ADMIN_TEMPLATE", "blog_admin_tpl.php");

define("PATH_DELIM", strtoupper(substr(PHP_OS,0,3)=='WIN')?'\\':'/');

# Get the theme for the blog.  I'd like to do this in the Blog class, but 
# we need to set the constant even when we don't have a current blog, so 
# we'll do it here instead.
if ( defined("BLOG_ROOT") ) {
	$cfg_file = BLOG_ROOT.PATH_DELIM.BLOG_CONFIG_PATH;
	if (is_file($cfg_file)) {
		$data = file($cfg_file);
		foreach ($data as $line) {
			$arr = explode("=", $line);
			if (strtolower(trim($arr[0])) == "theme") {
				define("THEME_NAME", trim($arr[1]));
				break;
			}
		}
	}
}

# Set constants to make themes work.
if (! defined("THEME_NAME")) define("THEME_NAME", "default");
if (! defined("INSTALL_ROOT")) define("INSTALL_ROOT", getcwd());
if (! defined("INSTALL_ROOT_URL")) define("INSTALL_ROOT_URL", "./");
define("THEME_TEMPLATES", "themes".PATH_DELIM.THEME_NAME.PATH_DELIM."templates");
define("DEFAULT_THEME_TEMPLATES", "themes".PATH_DELIM."default".PATH_DELIM."templates");
if (! defined("THEME_STYLES")) 
	define("THEME_STYLES", INSTALL_ROOT_URL."themes/".THEME_NAME."/styles");
if (! defined("THEME_IMAGES")) 
	define("THEME_IMAGES", INSTALL_ROOT_URL."themes/".THEME_NAME."/images");
if (! defined("THEME_SCRIPTS")) 
	define("THEME_SCRIPTS", INSTALL_ROOT_URL."themes/".THEME_NAME."/scripts");

# Add class libraries and pages to the include path.
ini_set("include_path", ini_get("include_path").
	PATH_SEPARATOR.INSTALL_ROOT.PATH_DELIM."lib".
	PATH_SEPARATOR.INSTALL_ROOT.PATH_DELIM."pages");
# Add the theme and other templates from the blog directory.  These will 
# override the system defaults if they exist.
if (defined("BLOG_ROOT")) ini_set("include_path", ini_get("include_path").
	PATH_SEPARATOR.BLOG_ROOT.PATH_DELIM.THEME_TEMPLATES.
	PATH_SEPARATOR.BLOG_ROOT.PATH_DELIM."templates");
# Add the installation-wide theme and default theme to the include path.  
# This will be the fall-back if the user theme does not exist.
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.
	INSTALL_ROOT.PATH_DELIM.THEME_TEMPLATES.
	PATH_SEPARATOR.INSTALL_ROOT.PATH_DELIM.DEFAULT_THEME_TEMPLATES);
	
?>
