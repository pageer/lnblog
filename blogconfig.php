<?php

# Information on the package itself.
define("PACKAGE_NAME", "LnBlog");
define("PACKAGE_VERSION", "0.1.0");
define("PACKAGE_URL", "http://www.skepticats.com/lnblog/");
define("PACKAGE_DESCRIPTION", PACKAGE_NAME.": a simple and elegant weblog");
define("PACKAGE_COPYRIGHT", "Copyright (c) 2005, Peter A. Geer <pageer@skepticats.com>");

#define("FS_PLUGIN", "ftpfs");
# Miscellaneous configuration.
# This is the regular expression used to determine if a local path refers to
# a user's web root, which would be referred to with a URI like
# http://www.example.com/~jowblow/
# The generated URI will use the parent of the document root, e.g.
# /home/users/joeblow/www/ would be translated to /~joeblow/.
# Depending on your setup, you may need to add components before the username
# or change the document root from www to something else.  Of course, if you
# don't use ~user directories, you can probably ignore this.
define("LOCALPATH_TO_URI_MATCH_RE", "/^\/home\/[^\/]+\/www/");

# Blog configuration
define("BLOG_CONFIG_PATH", "blogdata.txt");  # File to store blog data
define("BLOG_DELETED_PATH", "deleted");      # Directory for old data
define("BLOG_ENTRY_PATH", "entries");        # Directory for blog entries
define("BLOG_ARTICLE_PATH", "content");      # Directory for articles
define("BLOG_FEED_PATH", "feeds");           # Directory for RSS feeds
define("BLOG_RSS1_NAME", "news.rdf");        # RSS 1.0 "link only" filename
define("BLOG_RSS2_NAME", "news.xml");        # RSS 2.0 "full text" filename
define("BLOG_MAX_ENTRIES", 10);              # Default number of entries to 
                                             # put on front page.

# Blog entry configuration
# The formats here are passed to strftime(), so check the docs.
define("ENTRY_PATH_FORMAT", "d_Hi");         # Used for entry directory
define("ENTRY_PATH_FORMAT_LONG", "d_His");   # Used for old data file names
define("ENTRY_DEFAULT_FILE", "current.htm"); # Name of data file
define("ENTRY_DATE_FORMAT", "Y-m-d H:i T");  # Format to display post dates
define("ENTRY_COMMENT_DIR", "comments");     # Comment directory
define("ENTRY_PATH_SUFFIX", ".htm");         # Extension for data files
define("STICKY_PATH", "sticky.txt");         # If present, show articel in sidebar

# These are constants for the entry submission form.  
# Is this the best way to do it?
define("ENTRY_POST_SUBJECT", "subject");
define("ENTRY_POST_ABSTRACT", "abstract");
define("ENTRY_POST_COMMENTS", "allow_comments");
define("ENTRY_POST_DATA", "data");
define("ENTRY_POST_HTML", "usehtml");

# Comment configuration
define("COMMENT_NOFOLLOW", true);             # Use Google rel="nofollow"?
define("ANON_POST_NAME", "Anonymous Reader"); # Displayed when no name given
define("NO_SUBJECT", "No Subject");           # Ditto, but for subjet
define("COMMENT_PATH_FORMAT", "Y-m-d_His");   # File name format
define("COMMENT_PATH_SUFFIX", ".txt");        # Extension for comment files
define("COMMENT_DELETED_PATH", "deleted");    # Directory for deleted files
# More form constants.  Same deal as for entries.
define("COMMENT_POST_SUBJECT", "subject");
define("COMMENT_POST_NAME", "username");
define("COMMENT_POST_EMAIL", "e-mail");
define("COMMENT_POST_URL", "url");
define("COMMENT_POST_DATA", "data");
define("COMMENT_POST_REMEMBER", "remember");

# Template file data.
# These are just constants for the names of template files.
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
define("ARTICLE_EDIT_TEMPLATE", "article_edit_tpl.php");
define("LOGIN_TEMPLATE", "login_tpl.php");
define("CREATE_LOGIN_TEMPLATE", "login_create_tpl.php");
define("CONFIRM_TEMPLATE", "confirm_tpl.php");
define("BLOG_UPDATE_TEMPLATE", "blog_modify_tpl.php");
define("BLOG_ADMIN_TEMPLATE", "blog_admin_tpl.php");
define("UPLOAD_TEMPLATE", "upload_form_tpl.php");
define("FS_CONFIG_TEMPLATE", "fs_config_tpl.php");

# Path delimiter for local paths.
define("PATH_DELIM", strtoupper(substr(PHP_OS,0,3)=='WIN')?'\\':'/');

# Define config files for various parts of the plugin framework.
define("FS_PLUGIN_CONFIG", "fsconfig.php");  # Filesystem writing

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

# Set constants to make themes work.  Most of these are defaults for when 
# there is not current blog set up.
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
# override the defaults if they exist.
if (defined("BLOG_ROOT")) 
	ini_set("include_path", ini_get("include_path").
		PATH_SEPARATOR.BLOG_ROOT.PATH_DELIM.THEME_TEMPLATES.
		PATH_SEPARATOR.BLOG_ROOT.PATH_DELIM."templates");
# Add the installation-wide theme and default theme to the include path.  
# This will be the fall-back if the user theme does not exist.
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.
	INSTALL_ROOT.PATH_DELIM.THEME_TEMPLATES.
	PATH_SEPARATOR.INSTALL_ROOT.PATH_DELIM.DEFAULT_THEME_TEMPLATES);
	
?>
