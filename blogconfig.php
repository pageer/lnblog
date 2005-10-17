<?php
/*
File: blogconfig.php
Holds basic configuration constants that are required by all pages.
Some of these constants can be over-ridden in the userdata/userconfig.php
file, which can be created by users.
*/

# Turn off runtime magic quotes so that we don't have to filter input from 
# files to remove the escape characters.
ini_set("magic_quotes_runtime", "off");

# Directory name where user data is stored.
define("USER_DATA", "userdata");

# Path delimiter for local paths.
define("PATH_DELIM", strtoupper(substr(PHP_OS,0,3)=='WIN')?'\\':'/');

# Check for the file's existence first, because it turns out this is 
# actually faster when the file doesn't exist.
if (defined("INSTALL_ROOT") && 
    file_exists(INSTALL_ROOT.PATH_DELIM.USER_DATA.PATH_DELIM.
	             "userconfig.php") ) {
	include(USER_DATA."/userconfig.php");
} elseif (! defined("INSTALL_ROOT") ) {
	@include(USER_DATA."/userconfig.php");
}

# Information on the package itself.
define("PACKAGE_NAME", "LnBlog");
define("PACKAGE_VERSION", "0.4.0");
define("PACKAGE_URL", "http://www.skepticats.com/lnblog/");
define("PACKAGE_DESCRIPTION", PACKAGE_NAME.": a simple and (hopefully) elegant weblog");
define("PACKAGE_COPYRIGHT", "Copyright (c) 2005, Peter A. Geer <pageer@skepticats.com>");

# Section: Miscellaneous configuration
# Assorted configuration constants that don't fit into any coherent category.

/*
Constant: LOCALPATH_TO_URI_MATCH_RE
This is the regular expression used to determine if a local path refers to
a user's web root, which would be referred to with a URI like
http://www.example.com/~jowblow/
and vice versa.  The generated URI will use the parent of the document root, e.g.
/home/users/joeblow/www/ would be translated to /~joeblow/.
Depending on your setup, you may need to add components before the username
or change the document root from www to something else.  Of course, if you
don't use ~user directories, you can probably ignore this. */
@define("LOCALPATH_TO_URI_MATCH_RE", "/^\/home\/([^\/]+)\/www(.*)/");
/* Constant: LOCALPATH_TO_URI_REPLACE_RE
The corresponding replacement pattern to <LOCALPATH_TO_URI_MATCH_RE>. */
@define("LOCALPATH_TO_URI_REPLACE_RE", "/~$1$2");
/* Constant: URI_TO_LOCALPATH_MATCH_RE
The reverse of <LOCALPATH_TO_URI_MATCH_RE>.  This is the regular expression 
that matches part of a root-relative URI and extracts a directory name. */
@define("URI_TO_LOCALPATH_MATCH_RE", "/^\/~([^\/]+)(.*)/");
/* Constant: URI_TO_LOCALPATH_REPLACE_RE
The corresponding replacement expression to <URI_TO_LOCALPATH_MATCH_RE>. */
@define("URI_TO_LOCALPATH_REPLACE_RE", "$2"); 

# Constant: UNICODE_ESCAPE_HACK
# Use an ugly hack with htmlentities() instead of the simpler 
# htmlspecialchars() to deal  with problems displaying Unicode characters.
@define("UNICODE_ESCAPE_HACK", false);

# Constant: SITEMAP_FILE
# The file to save the sitemap link list for the menubar.
@define("SITEMAP_FILE", "sitemap.htm");

# Constant: DOCROOT_NAMES
# The list of directory names that can be the document root.  The 
# find_document_root() function searches up the tree from its starting
# directory and terminates when it finds something in the list.
# If you define a DOCUMENT_ROOT when configuring file writing, then 
# find_document_root() uses that and this constant is ignored.
@define("DOCROOT_NAMES", "wwwroot,inetpub,htdocs,htsdocs,httpdocs,httpsdocs,webroot,www,html");

# Constant: LBCODE_HEADER_WEIGHT
# Defines the level of header used for the [h] LBCode tag.
@define("LBCODE_HEADER_WEIGHT", 3);

# Section: User Authentication
# Constants that relate to user authentication

# Constant: AUTH_USE_SESSION
# Use sessions in authentication or just cookies.
@define("AUTH_USE_SESSION", true);

# Constant: ADMIN_USER
# Username of site administrator.  This is the only one who can add or 
# delete blogs.
@define("ADMIN_USER", "administrator");

# Blog configuration
@define("BLOG_CONFIG_PATH", "blogdata.txt");  # File to store blog data
@define("BLOG_DELETED_PATH", "deleted");      # Directory for old data
@define("BLOG_ENTRY_PATH", "entries");        # Directory for blog entries
@define("BLOG_ARTICLE_PATH", "content");      # Directory for articles
@define("BLOG_FEED_PATH", "feeds");           # Directory for RSS feeds
@define("BLOG_RSS1_NAME", "news.rdf");        # RSS 1.0 "link only" filename
@define("BLOG_RSS2_NAME", "news.xml");        # RSS 2.0 "full text" filename
@define("BLOG_MAX_ENTRIES", 10);              # Default number of entries to 
                                              # put on front page.

# Blog entry configuration
# The formats here are passed to strftime(), so check the docs.
@define("ENTRY_PATH_FORMAT", "d_Hi");         # Used for entry directory
@define("ENTRY_PATH_FORMAT_LONG", "d_His");   # Used for old data file names
@define("ENTRY_DEFAULT_FILE", "current.htm"); # Name of data file
@define("ENTRY_DATE_FORMAT", "Y-m-d H:i T");  # Format to display post dates
@define("ENTRY_COMMENT_DIR", "comments");     # Comment directory
@define("ENTRY_TRACKBACK_DIR", "trackback");  # Dir to store TrackBack pings.
@define("ENTRY_PATH_SUFFIX", ".htm");         # Extension for entry data files
@define("STICKY_PATH", "sticky.txt");         # If present, show article in sidebar
@define("COMMENT_RSS1_PATH", "comments.rdf"); # RSS 1.0 feed for comments
@define("COMMENT_RSS2_PATH", "comments.xml"); # RSS 2.0 feed for comments.

# Trackback configuration
@define("TRACKBACK_PATH_SUFFIX", ".txt");     # File suffix for saved pings.

# These are constants for the entry submission form.  
@define("ENTRY_POST_SUBJECT", "subject");
@define("ENTRY_POST_ABSTRACT", "abstract");
@define("ENTRY_POST_COMMENTS", "allow_comments");
@define("ENTRY_POST_DATA", "data");
@define("ENTRY_POST_HTML", "usehtml");

/*
Section: Comments
Configuration constants used for controlling reader comments.
*/
/* Constant: COMMENT_NOFOLLOW
Determine whether to use the rel="nofollow" attribute in links in comments.
This attribute is a comment spam fighting measure instituted by Google, who
will not index links with this attribute.  *Default* is true. */
@define("COMMENT_NOFOLLOW", true);
/* Constant: ANON_POST_NAME
Default for users who don't enter a name. *Default* is "Anonymous Reader". */
@define("ANON_POST_NAME", "Anonymous Reader");
/* Constant: NO_SUBJECT
Default text if the user does not enter a subject.  
*Default* is "No Subject". */
@define("NO_SUBJECT", "No Subject");
/* Constant: COMMENT_PATH_FORMAT
Date format string to use for comment file names.  The *default* is 
"Y-m-d_His". */
@define("COMMENT_PATH_FORMAT", "Y-m-d_His");
/* Constant: COMMENT_PATH_SUFFIX
File suffix to use for comment files.  *Default* is ".txt". */
@define("COMMENT_PATH_SUFFIX", ".txt");
/* Constant: COMMENT_DELETED_PATH
Leaf directory name where deleted comments are stored.  The *default* is 
"deleted".*/
@define("COMMENT_DELETED_PATH", "deleted");
/* Constant: COMMENT_EMAIL_VIEW_STRICT
Make e-mail addresses in comments visible to everybody.  By default, only
logged-in users can see e-mail addresses. */
@define("COMMENT_EMAIL_VIEW_PUBLIC", false);

# More form constants.  Same deal as for entries.
@define("COMMENT_POST_SUBJECT", "subject");
@define("COMMENT_POST_NAME", "username");
@define("COMMENT_POST_EMAIL", "e-mail");
@define("COMMENT_POST_URL", "url");
@define("COMMENT_POST_DATA", "data");
@define("COMMENT_POST_REMEMBER", "remember");

#----------------------------------------------------------------------------
# This section should not normally need to be changed.

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
@define("THEME_NAME", "default");
@define("INSTALL_ROOT", getcwd());
@define("INSTALL_ROOT_URL", "./");
define("THEME_TEMPLATES", "themes".PATH_DELIM.THEME_NAME.PATH_DELIM."templates");
define("DEFAULT_THEME_TEMPLATES", "themes".PATH_DELIM."default".PATH_DELIM."templates");
@define("THEME_STYLES", INSTALL_ROOT_URL."themes/".THEME_NAME."/styles");
@define("THEME_IMAGES", INSTALL_ROOT_URL."themes/".THEME_NAME."/images");
@define("THEME_SCRIPTS", INSTALL_ROOT_URL."themes/".THEME_NAME."/scripts");
@define("DEFAULT_STYLES", INSTALL_ROOT_URL."themes/default/styles");
@define("DEFAULT_IMAGES", INSTALL_ROOT_URL."themes/default/images");
@define("DEFAULT_SCRIPTS", INSTALL_ROOT_URL."themes/default/scripts");

# Set the path for system-wide user data files.
@define("USER_DATA_PATH", INSTALL_ROOT.PATH_DELIM.USER_DATA);

# Include constants for classes.
require_once("lib/constants.php");
# Add the theme and other templates from the blog directory.  These will 
# override the defaults if they exist.
if (defined("BLOG_ROOT")) 
	ini_set("include_path", ini_get("include_path").
		PATH_SEPARATOR.BLOG_ROOT.PATH_DELIM.THEME_TEMPLATES.
		PATH_SEPARATOR.BLOG_ROOT.PATH_DELIM."templates");
# Add the installation-wide theme and default theme to the include path.  
# This will be the fall-back if the user theme does not exist.
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.
	INSTALL_ROOT.PATH_SEPARATOR.
	(THEME_NAME != "default"?INSTALL_ROOT.PATH_DELIM.THEME_TEMPLATES.PATH_SEPARATOR:"").
	INSTALL_ROOT.PATH_DELIM.DEFAULT_THEME_TEMPLATES);
# Now that everything is initialized, we can create the global event register
# and plugin manager.
require_once("lib/eventregister.php");
require_once("lib/pluginmanager.php");

?>
