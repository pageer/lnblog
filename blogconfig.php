<?php
# File: blogconfig.php
# Holds basic configuration constants that are required by all pages.
# Some of these constants can be over-ridden in either the 
# userdata/userconfig.php or userdata/userconfig.cfg files.  Those files 
# do not exist in the default installation, but if created
# by the user, it will be executed before the body of this file.

# Turn off runtime magic quotes so that we don't have to filter input from 
# files to remove the escape characters.
ini_set("magic_quotes_runtime", "off");

##########################################
# Section: Essentials

# Constant: USER_DATA
# Directory name where user data is stored. The standard name is "userdata".
define("USER_DATA", "userdata");

# Constant: PATH_DELIM
# Path delimiter for local paths.  This is calculated at run-time and
# is set to '/' for UNIX-like systems and '\' for Windows systems.
define("PATH_DELIM", strtoupper(substr(PHP_OS,0,3)=='WIN')?'\\':'/');

# Function: mkpath
# A globally accessible convenience function that takes a variable number
# of arguments and strings them together with PATH_DELIM.
#
# Returns:
# A string with each of the arguments separated by PATH_DELIM.
function mkpath() {
	$args = func_get_args();
	return implode(PATH_DELIM, $args);
}

# Function: get_blog_path
# A quick global function to define get the path to the current blog even
# if the BLOG_ROOT constant is not defined.
#
# Returns:
# The full path to the blog.
function get_blog_path() {
	if ( defined("BLOG_ROOT") ) {
		return BLOG_ROOT;
	} elseif (isset($_GET['blog'])) {
		$path = $_GET['blog'];
		if (PATH_DELIM != "/") {
			$path = str_replace("/", PATH_DELIM, $path);
		}
		$path = preg_replace("/[^\w|"."\\".PATH_DELIM."]/", "", $path);
		return DOCUMENT_ROOT.PATH_DELIM.$path;
	} else {
		return false;
	}
}

# Function: load_plugin
# Loads a plugin.  This is used for plugins that do page output and will result 
# in the plugin outputing its markup.  Note that not all plugins support this 
# and for those that do, it is configurable as an option, with the alternative 
# being using the event system.
#
# Parameters:
# plugin_name - The name of the class for this plugin.
#
# Returns:
# An instance of the plugin.  The return value may safely be disregarded.
function load_plugin($plugin_name) {
	return new $plugin_name(true);
}

# Define config files for various parts of the plugin framework.
define("FS_PLUGIN_CONFIG", "fsconfig.php");

# Since we were able to include this file, we must already have the installation
# directory in the path.  So we can just include userdata/fsconfig.php and not
# have to worry about the exact path.
@include_once(USER_DATA."/".FS_PLUGIN_CONFIG);

# If we do not have the INSTALL_ROOT defined from a previously included
# pathconfig.php, then we need to take care of that.  We'll try to get the
# blog's pathconfig.php file, and failing that, we'll just use the cwd.
if (! defined("INSTALL_ROOT")) {

	if ( get_blog_path() ) {
		$temp = ini_get('include_path');
		ini_set('include_path', get_blog_path());
		require_once('pathconfig.php');
		ini_set('include_path', $temp);
	}

	if (! defined("INSTALL_ROOT")) define("INSTALL_ROOT", getcwd());

}
/*if (! defined("BLOG_ROOT") && 
      file_exists(get_blog_path().PATH_DELIM."pathconfig.php")) {
	require_once("pathconfig.php");
}*/

# Set the path for system-wide user data files.
define("USER_DATA_PATH", INSTALL_ROOT.PATH_DELIM.USER_DATA);

# Check for the file's existence first, because it turns out this is 
# actually faster when the file doesn't exist.
if (file_exists(mkpath(INSTALL_ROOT,USER_DATA,"userconfig.php")) ) {
	include(USER_DATA."/userconfig.php");
}

# Look for the userconfig.cfg and define any variables in it.
# This is a more "user friendly" alternative to userconfig.php.
if (file_exists(mkpath(INSTALL_ROOT,USER_DATA,"userconfig.cfg"))) {
	$data = file(mkpath(INSTALL_ROOT,USER_DATA,"userconfig.cfg"));
	foreach ($data as $line) {
		# Skip comments lines starting with a hash.
		if (preg_match("/^\s*#/", $line)) continue;
		$pieces = explode("=", $line, 2);
		if (count($pieces) == 2) {
			$pieces[1] = trim($pieces[1]);
			$curr_piece = strtolower($pieces[1]);
			
			if ($curr_piece == "true") $pieces[1] = true;
			elseif ($curr_piece == "false") $pieces[1] = false;
			
			define(strtoupper(trim($pieces[0])), $pieces[1]);
		}
	}
}

# Function: read_file
# Reads file contents, respecting the values of the LNBLOG_OPEN_BASEDIR and 
# LNBLOG_PARANOIA configuration constants.
#
# If LNBLOG_OPEN_BASEDIR and LNBLOG_PARANOIA are not defined or set to false, 
# then this function behaves exactly like the standard file() function.
#
# If LNBLOG_OPEN_BASEDIR is set, it will return false for any attempt to read a 
# file that is above that directory in the filesystem.  So for example, if 
# LNBLOG_OPEN_BASEDIR = /var/www/demo, then this will return the
# contents of /var/www/demo/file.txt or /var/www/demo/foo/bar.txt, but it will
# return false for /var/www/myfile.txt
#
# If LNBLOG_PARANOIA is set, this will return false when attempting to open any
# file with a .php extension.  So, for example, it would open 
# /var/www/demo/foo.txt, but would return false for /var/www/demo/foo.php.
function read_file($filename) {
	
}

# Put this definition after we initialize userconfig.cfg, so that it can be
# changed.

# Constant: URI_TYPE
# Tells the system what kind of URIs to generate.
# The available values are as follows.
# directory   - Convert local directory paths directly into URIs using the old
#               wrapper script method.
# querystring - Use ugly old query string URIs.
# htaccess    - Use query strings made pretty with Apache rewrite rules.
#               Note that this only works on Apache servers with support for
#               mod_rewrite and .htaccess files.
# The *default* mode is "directory".
@define("URI_TYPE", "directory");

##############################################
# Section Internationalization
# Core configuration for i18n.

# Constant: DEFAULT_LANGUAGE
# The default language.  
# If a translation does not exist for your specified language, then
# this will be used as the "last resort."  This is set to "en_US".
define("DEFAULT_LANGUAGE", "en_US");

# Constant: LANGUAGE
# The language to use for text.
# This setting controls which language is used for the templates and other
# literal text that LnBlog outputs.
#
# The default is to use the value of the LANG environment variable, if it is
# set.  If not, this is set to the <DEFAULT_LANGUAGE> and the LANG 
# environment variable is set.
#
# Please note that this constant is also used to set the locale, as follows.
# |setlocale(LC_ALL, LANGUAGE);
# Because of this, it is important to note that Windows uses different values
# for languages than UNIX and Linux systems.  In Linux, for example, a
# German locale might be "de_DE", whereas on Windows it woule be "deu".
if (getenv("LANG")) {
	@define("LANGUAGE", getenv("LANG"));
} else {
	@define("LANGUAGE", DEFAULT_LANGUAGE);
	putenv("LANG=".LANGUAGE);
}

# Constant: LOCALEDIR
# Directory to store the localization files.
# This is used by both GNU gettext and the ad hoc translation system.
define("LOCALEDIR", "locale");

#########################################
# Section: Package information
# Information on the package itself.

# Constant: PACKAGE_NAME
# The official package name, currently "LnBlog".
define("PACKAGE_NAME", "LnBlog");

# Constant: PACKAGE_VERSION
# The version number of the software.  This is a string in the format 
# "1.2.3".  Note that each number may be more than one digit.
define("PACKAGE_VERSION", "0.7.3");

# Constant: PACKAGE_URL
# The full URL of the LnBlog project home page.
define("PACKAGE_URL", "http://www.skepticats.com/lnblog/");

# Add I18N support here, as this is currently the earliest we can do it.
# Refer to the lib/i18n.php file for details.
require_once("lib/i18n.php");

# Constant: PACKAGE_DESCRIPTION
# The offical text-blurb description of the software.
define("PACKAGE_DESCRIPTION", spf_("%s: a simple and (hopefully) elegant weblog", PACKAGE_NAME));

# Constant: PACKAGE_COPYRIGHT
# The offical LnBlog copyright notice.
define("PACKAGE_COPYRIGHT", _("Copyright (c) 2005, Peter A. Geer <pageer@skepticats.com>"));

###############################################
# Section: Page DOCTYPEs
# These are full, valid doctypes to use for pages.  See <DEFAULT_DOCTYPE>.

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

######################################
# Section: Miscellaneous configuration
# Assorted configuration constants that don't fit into any coherent category.

# Constant: USE_WRAPPER_SCRIPTS
# Controls the use of PHP wrapper scripts for pages.
# When this is turned on, LnBlog will use a series of wrapper PHP scripts to
# include the files that build pages.  This results in a clean, 
# directory-based URL structure.
#
# If this is set to false, then LnBlog will revert to a URL scheme
# based on query strings.  The up side of doing things this way is that 
# you never need to upgrade the wrapper scripts.  You can achieve a clean
# URL structure this way if you have Apache and an appropriate .htacces file.
#
# *Defaults* to true.  
# *Note:* this is not yet implemented.
@define("USE_WRAPPER_SCRIPTS", true);

# Constant: DEFAULT_DOCTYPE
# The DOCTYPE used by your markup.  
#
# The *default* is XHTML 1.0 Strict.
@define("DEFAULT_DOCTYPE", XHTML_1_0_Strict);

# Constant: LOGIN_EXPIRE_TIME
# The expiration time for the login cookies.
#
# If set to an integer value, the cookies used for user logins will expire
# that number of seconds from the current time.  So, if you set this to 3600,
# then the user will be logged out in 1 hour.
#
# The *default* is false, which means cookies expire when the session ends.
@define("LOGIN_EXPIRE_TIME", false);

# Constant: DEFAULT_CHARSET
# The default character encoding.
# This is the encoding included in the Content-Type meta tag for each page.
#
# There are two defaults for this setting.  If the *default_charset*
# INI variable is set, then that value is used.  If it is not set, then
# the default is to use "utf-8", which is an ASCII-compatible Unicode encoding.
if (ini_get("default_charset")) {
	@define("DEFAULT_CHARSET", ini_get("default_charset"));
} elseif (! defined("DEFAULT_CHARSET")) {
	define("DEFAULT_CHARSET", "utf-8");
}

# Constant: DEFAULT_MIME_TYPE
# The default MIME type to accompany <DEFAULT_CHARSET> in the Content-Type.
# 
# As with <DEFAULT_CHARSET>, there are two possible defaults for this.  If
# the *default_mimetype* INI variable is set, then that value is used.
# If it is not, then the default is "text/html".
if (ini_get("default_mimetype")) {
	@define("DEFAULT_MIME_TYPE", ini_get("default_mimetype"));
} elseif (! defined("DEFAULT_MIME_TYPE")) {
	@define("DEFAULT_MIME_TYPE", "text/html");
}

# Constant: USE_STRFTIME
# Determine whether to use strftime() or date() for user-readable dates.
#
# *Defaults* to true.  When set to false, things like entry dates will be 
# formatted using the function date() instead of strftime().  If you
# change this, you will also need to change the <ENTRY_DATE_FORMAT>.
@define("USE_STRFTIME", true);

# Constant: SYSTEM_CHARSET
# The character set used by the system when returning generated text.
#
# This is used in conjunction with <USE_STRFTIME> to automatically convert
# characters.  By *default*, this is not set.  When it is set *and* the 
# iconv extension is enabled, then the dates will be converted from this 
# character set to the <DEFAULT_CHARSET>.

/*
Constant: LOCALPATH_TO_URI_MATCH_RE
This is the regular expression used to determine if a local path refers to
a user's web root, which would be referred to with a URI like
http://www.example.com/~jowblow/
and vice versa.  The generated URI will use the parent of the document root, e.g.
/home/users/joeblow/www/ would be translated to /~joeblow/.
Depending on your setup, you may need to add components before the username
or change the document root from www to something else.  Of course, if you
don't use ~user directories, you can probably ignore this. 

*Default* is "/^\/home\/([^\/]+)\/www(.*)/i".
*/
@define("LOCALPATH_TO_URI_MATCH_RE", "/^\/home\/([^\/]+)\/(www|public_html)\/(.*)/i");

/* Constant: LOCALPATH_TO_URI_REPLACE_RE
The corresponding replacement pattern to <LOCALPATH_TO_URI_MATCH_RE>. 

*Default* is "/~$1$2".
*/
@define("LOCALPATH_TO_URI_REPLACE_RE", "/~$1/$3");

/* Constant: URI_TO_LOCALPATH_MATCH_RE
The reverse of <LOCALPATH_TO_URI_MATCH_RE>.  This is the regular expression 
that matches part of a root-relative URI and extracts a directory name. 

*Default* is "/^\/~([^\/]+)(.*)/i".
*/
@define("URI_TO_LOCALPATH_MATCH_RE", "/^\/~([^\/]+)(.*)/i");

/* Constant: URI_TO_LOCALPATH_REPLACE_RE
The corresponding replacement expression to <URI_TO_LOCALPATH_MATCH_RE>. 

*Default* is "$2".
*/
@define("URI_TO_LOCALPATH_REPLACE_RE", "$2"); 

# Constant: UNICODE_ESCAPE_HACK
# Use an ugly hack with htmlentities() instead of the simpler 
# htmlspecialchars() to deal with problems displaying Unicode characters.
# 
# This is to account for character encoding issues.  The basic situation is
# that, for whatever reason, it appears that if the many browsers will 
# automatically escape characters in the wrong encoding to numeric HTML
# character codes.  When sanitizing this input with htmlentities(), the 
# ampersands get converted to &amp; and so need to be converted back.
# When this is set to true, that's what happens.
#
# When this is set to false, input will only be sanitized with
# htmlspecialchars(), which does fewer substitutions in the first place.  
# This mode does not effect the above mentioned auto-conversion, so you 
# should only use this if you're sure nobody is going to be posting to the
# site in a character set that differs from the one defined by the server.
#
# Note that this setting only applies to posts (including comments) that use
# LBCode or auto-markup.  Raw HTML code is left completely unmodified.
#
# *Default* is true.
@define("UNICODE_ESCAPE_HACK", true);

# Constant: SITEMAP_FILE
# The file to save the sitemap link list for the menubar.
# 
# *Default* is "sitemap.htm".
@define("SITEMAP_FILE", "sitemap.htm");

# Constant: LBCODE_HEADER_WEIGHT
# Defines the level of header used for the [h] LBCode tag.
#
# *Default* is 3.
@define("LBCODE_HEADER_WEIGHT", 3);

# Constant: EDITOR_HIDE_INLINE_BOXES
# Shows the inline boxes used for old version of the JavaScript editor
#
# *Default* is false.
@define("EDITOR_SHOW_INLINE_BOXES", false);

# Constant: EDITOR_HIDE_SYMBOLS
# Shows the the buttons to add entities for mathamatical symbols.
#
# *Default* is false.
@define("EDITOR_SHOW_SYMBOLS", false);

########################################
# Section: User Authentication
# Constants that relate to user authentication

# Constant: AUTH_USE_SESSION
# Use sessions in authentication instead of just cookies.
#
# *Default* is true.
@define("AUTH_USE_SESSION", true);

# Constant: ADMIN_USER
# Username of site administrator.  This is the only one who can add or 
# delete blogs.
#
# *Default* is "administrator".
@define("ADMIN_USER", _("administrator"));

# Constant: CUSTOM_PROFILE
# File name use to store custom fields that will be listed in user profiles.
# This file is in INI format, with key names corresponding to the custom
# field names and values being the label displayed for the field.
@define("CUSTOM_PROFILE", "profile.ini");

# Constant: CUSTOM_PROFILE_SECTION
# The INI section of the <CUSTOM_PROFILE> file that holds the list
# of custom fields.
@define("CUSTOM_PROFILE_SECTION", "profile fields");

##########################################
# Section: Blog configuration
# Configuration constants regarding date formats and file names. 

# Constant: BLOG_CONFIG_PATH
# File name for storing blog metadata.
# Note that this is specific to file-based storage and should not be used
# for database storage.  
#
# *Default* is "blogdata.ini".
@define("BLOG_CONFIG_PATH", "blogdata.ini");

# Constant: BLOG_DELETED_PATH
# Directory name used to store old blog settings.
# Note that this is only for file-based storage that uses history-tracking.
#
# *Default* is "deleted".
@define("BLOG_DELETED_PATH", "deleted");

# Constant: BLOG_ENTRY_PATH
# The name of the subdirectory of the blog root URL under which blog entries 
# are accessed.  This may not necessarily be a real directory on the server.
#
# *Default* is "entries".
@define("BLOG_ENTRY_PATH", "entries");

# Constant: ENTRY_DRAFT_PATH
# Directory where draft entries are stored.
#
# Default is "drafts".
@define("ENTRY_DRAFT_PATH", "drafts");

# Constant: BLOG_ARTICLE_PATH
# Like <BLOG_ENTRY_PATH>, except for static articles.
#
# *Default* is "content".
@define("BLOG_ARTICLE_PATH", "content");

# Constant: BLOG_RSS_PATH
# Like <BLOG_ENTRY_PATH>, except for accessing RSS feeds.
#
# *Default* is "feeds".
@define("BLOG_FEED_PATH", "feeds");

# Constant: BLOG_MAX_ENTRIES
# Default number of entries to display on the front page.
#
# This constant is used as a default setting for creating new blogs 
# and can be over-ridden on a per-blog basis.  In other words, not
# every blog will use this value.
#
# *Default* is 10.
@define("BLOG_MAX_ENTRIES", 10);

###############################################
# Section: Blog entry and Article configuration

# Constant: KEEP_EDIT_HISTORY
# Controls whether deleted and modified entries are kept archived, or if
# they are just permanently deleted.
#
# The *default* is false, which mean that deleted items are gone forever.
# In previous versions, the default behavior was to save these, despite
# the fact that there was no interface for dealing with them.
@define("KEEP_EDIT_HISTORY", false);

# Constant: ENTRY_PATH_FORMAT
# The date format used directories for blog entries.
#
# The formats here are passed to the PHP strftime(), so check the docs on
# that for details.  The creation timestamp of the blog entry is used to 
# return the exact value.
#
# Programmers should also note that the "directories" referenced here are
# components of a URI and may not be real directories on the server.
#
# The *default* is "d_Hi", which is the day, hour, and minute.  For example,
# 10_1215 would be returned for 12:15 PM on the 10th of the month.
@define("ENTRY_PATH_FORMAT", "d_Hi");

# Constant: ENTRY_PATH_FORMAT_LONG
# An alternate to <ENTRY_PATH_FORMAT>.
#
# This format was used in earlier versions of the software, but is 
# retained in case there is a need to fall back to a longer format.
#
# The *default* is "d_His", which is the same as <ENTRY_PATH_FORMAT>, but 
# includes the seconds at the end.
@define("ENTRY_PATH_FORMAT_LONG", "d_His");

# Constant: ENTRY_DEFAULT_FILE
# The file name used to store the data for a blog enrty or article.
# Note that this is specific to file-based storage.
#
# The *default* is "current.htm".
@define("ENTRY_DEFAULT_FILE", "current.htm");

# Constant: TAG_SEPARATOR
# The character used to separate individual tags entered for entries.
#
# The *default* is a comma (",").  
@define("TAG_SEPARATOR", ",");

# Constant: ENTRY_DATE_FORMAT
# Format used to display dates for entries and articles.
# Note that, like <ENTRY_PATH_FORMAT> this string includes strftime() 
# scancodes.
#
# The *default* is "%Y-%m-%d %H:%M %Z", which gives a date like 
# "2005-08-16 04:26 EST".
@define("ENTRY_DATE_FORMAT", "%Y-%m-%d %H:%M %Z");

# Constant: ENTRY_COMMENT_DIR
# Directory name to access comments on entries and articles.
#
# *Default* is "comments".
@define("ENTRY_COMMENT_DIR", "comments");

# Constant: ENTRY_TRACKBACK_DIR
# Directory name to access trackbacks on entries and articles.
#
# *Default* is "trackback".
@define("ENTRY_TRACKBACK_DIR", "trackback");

# Constant: ENTRY_PINGBACK_DIR
# Directory name to access pingbacks on entries and articles.
#
# *Default* is "pingback".
@define("ENTRY_PINGBACK_DIR", "pingback");

# Constant: ENTRY_PATH_SUFFIX
# The file suffix for old versions of entries and articles.
# Note that this only applies to file-based storage when using history 
# tracking.
#
# The *default* is ".htm".
@define("ENTRY_PATH_SUFFIX", ".htm");

# Constant: STICKY_PATH
# The name of the "sticky" file for articles.
#
# In the file-based storage implementation, when this file exists in 
# an article directory, the article will be treated as "sticky," meaning
# it will be displayed in the default sidebar.  This file may contain some
# of the article metadata.  The exact content is intentionally left
# undocumented, but will include at least the subject.
#
# The *default* value is "sticky.txt".
@define("STICKY_PATH", "sticky.txt");

########################################
# Section: Trackback configuration

# Constant: TRACKBACK_PATH_SUFFIX
# File suffix used for storing TrackBacks.
# Note that this is specific to file-based storage.
#
# The *default* is ".txt".
@define("TRACKBACK_PATH_SUFFIX", ".txt");     # File suffix for saved pings.

########################################
# Section: Pingback configuration

# Constant: PINGBACK_PATH_SUFFIX
# File suffix used for storing Pingbacks.
# Note that this is specific to file-based storage.
#
# The *default* is ".txt".
@define("PINGBACK_PATH_SUFFIX", ".txt");     # File suffix for saved pings.

#############################################
# Section: Comment configuration
# Configuration constants used for controlling reader comments.

# Constant: COMMENT_NOFOLLOW
# Determine whether to use the rel="nofollow" attribute in links in comments.
# This attribute is a comment spam fighting measure instituted by Google, who
# will not index links with this attribute. 
#
#*Default* is true. 
@define("COMMENT_NOFOLLOW", true);

# Constant: KEEP_COMMENT_HISTORY
# Like <KEEP_EDIT_HISTORY>, except for comments.
#
# *Default* is false
# Again, this was previously on my default, but is now off.
@define("KEEP_COMMENT_HISTORY", false);

# Constant: ANON_POST_NAME
# Default name for users who don't enter a name. 
#
# *Default* is "Anonymous Reader". 
@define("ANON_POST_NAME", _("Anonymous Reader"));

# Constant: NO_SUBJECT
# Default text if the user does not enter a subject.  
#
# *Default* is "No Subject".
@define("NO_SUBJECT", _("No Subject"));

# Constant: COMMENT_PATH_FORMAT
# Date format string to use for comment file names. 
#
#The *default* is "Y-m-d_His". 
@define("COMMENT_PATH_FORMAT", "Y-m-d_His");

# Constant: COMMENT_PATH_SUFFIX
# File suffix to use for comment files.  
#
#*Default* is ".txt". 
@define("COMMENT_PATH_SUFFIX", ".txt");

# Constant: COMMENT_DELETED_PATH
# Leaf directory name where deleted comments are stored. 
#
# The *default* is "deleted".
@define("COMMENT_DELETED_PATH", "deleted");

# Constant: COMMENT_EMAIL_VIEW_PUBLIC
# Controls the default setting for whether e-mail addresses in comments are
# visible to everybody.  
#
# The *default* is false, which means only logged-in users can 
# see e-mail addresses specified as non-public.
@define("COMMENT_EMAIL_VIEW_PUBLIC", false);

#----------------------------------------------------------------------------
# This section should not normally need to be changed.

if (! defined("BLOG_ROOT") && get_blog_path() ) {
	define("BLOG_ROOT", get_blog_path());
}

# Get the theme for the blog.  I'd like to do this in the Blog class, but 
# we need to set the constant even when we don't have a current blog, so 
# we'll do it here instead.
if ( defined("BLOG_ROOT") ) {
	$cfg_file = BLOG_ROOT.PATH_DELIM.BLOG_CONFIG_PATH;
} elseif (isset($_GET['blog'])) {
	$cfg_file = DOCUMENT_ROOT.PATH_DELIM.
		preg_replace("/\W/", "", $_GET['blog']).PATH_DELIM.BLOG_CONFIG_PATH;
}

if (isset($cfg_file) && ! is_file($cfg_file)) {
	$cfg_file = substr_replace($cfg_file, "blogdata.txt", 
	                           strlen($cfg_file) - strlen(BLOG_CONFIG_PATH));
}

if (isset($cfg_file) && is_file($cfg_file)) {
	$data = file($cfg_file);
	foreach ($data as $line) {
		$arr = explode("=", $line);
		if (strtolower(trim($arr[0])) == "theme") {
			define("THEME_NAME", trim($arr[1]));
			break;
		}
	}
}

# Set constants to make themes work.  Most of these are defaults for when 
# there is no current blog set up.
@define("THEME_NAME", "default");
if (! defined("INSTALL_ROOT_URL")) {
	require_once("lib/utils.php");
	define("INSTALL_ROOT_URL", localpath_to_uri(INSTALL_ROOT, false));
}

$theme_templates = PATH_DELIM.mkpath("themes",THEME_NAME,"templates");

# Include constants for classes.
require_once("lib/constants.php");

$inc_path = ini_get('include_path');
# Add the INSTALL_ROOT to the include path, if it's not already there.
if (strpos($inc_path, INSTALL_ROOT) === false) {
	$inc_path .= PATH_SEPARATOR.INSTALL_ROOT;
}
# Add the theme and other templates from the blog directory.  These will 
# override the defaults if they exist.
if (defined("BLOG_ROOT")) {
	if (is_dir(BLOG_ROOT.PATH_DELIM.$theme_templates)) {
		$inc_path .= PATH_SEPARATOR.BLOG_ROOT.$theme_templates;
	}
	if (is_dir(BLOG_ROOT.PATH_DELIM."templates")) {
		$inc_path .= PATH_SEPARATOR.BLOG_ROOT.PATH_DELIM."templates";
	}
}

# Add the installation-wide theme and default theme to the include path.  
# This will be the fall-back if the user theme does not exist.
if (THEME_NAME != "default") {
	$inc_path .= PATH_SEPARATOR.INSTALL_ROOT.$theme_templates;
}
if (is_dir(USER_DATA_PATH.PATH_DELIM.$theme_templates)) {
	$inc_path .= PATH_SEPARATOR.USER_DATA_PATH.$theme_templates;
}
# Add the default theme template to the include path.  This is for fall-back 
# when a template doesn't exist in a theme.
$inc_path .= PATH_SEPARATOR.mkpath(INSTALL_ROOT,"themes","default","templates");

# Finally, we actuall set the include_path.
ini_set('include_path', $inc_path);

# Now that everything is initialized, we can create the global event register
# and plugin manager and create a top-level page for handling output..
require_once("lib/eventregister.php");
require_once("lib/page.php");
require_once('lib/system.php');
require_once("lib/pluginmanager.php");

?>
