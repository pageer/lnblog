<?php

# File: blogconfig.php
# Holds basic configuration constants that are required by all pages.
# Some of these constants can be over-ridden in either the
# userdata/userconfig.php or userdata/userconfig.cfg files.  Those files
# do not exist in the default installation, but if created
# by the user, it will be executed before the body of this file.

# Pull in the various function's we'll need.
require_once __DIR__."/lib/utils.php";

# Turn off runtime magic quotes so that we don't have to filter input from
# files to remove the escape characters.
ini_set("magic_quotes_runtime", "off");

# Load Composer's autoloader first.
require_once __DIR__.'/vendor/autoload.php';

# Prepend our own autoloaders to the queue.
#
spl_autoload_register(get_class_autoload(), true, true);

# Pull in the base system config info and set up the handful of legacy constants
# that we still depend on.
SystemConfig::instance()->definePathConstants($BLOG_ROOT_DIR ?? '');

##########################################
# Section: Essentials

# Constant: PATH_DELIM
# Alias for DIRECTORY_SEPARATOR
define("PATH_DELIM", DIRECTORY_SEPARATOR);

# Load the userconfig.cfg file and determine the userdata path.

load_config_file();

# Define config files for various parts of the plugin framework.
define("FS_PLUGIN_CONFIG", "fsconfig.php");

# Since we were able to include this file, we must already have the installation
# directory in the path.  So we can just include userdata/fsconfig.php and not
# have to worry about the exact path.
@include_once(Path::mk(USER_DATA_PATH, FS_PLUGIN_CONFIG));

# Constant: USE_CRON_SCRIPT
# Determines whether tasks should be run asynchronously using cron or
# some other scheuling system.  If this is false, tasks will be run on
# page load, which can impact performance and execution time-frame.
# Note that this affects scheduled publishing, so the default is false.
@define("USE_CRON_SCRIPT", false);

# Constant: EMAIL_FROM_ADDRESS
# The "from" address that should be used for any e-mails that are sent.
# This will be used in the "from" field with an appropriate human-readable
# name, such as "From: LnBlog comment notifier <whatever@yourdomain.com>".
@define("EMAIL_FROM_ADDRESS", "");

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
define("PACKAGE_VERSION", "2.3.2");

# Constant: REQUIRED_VERSION
# The minimum software version required by your blog to properly
# support this version of LnBlog.  In other words, if your blog is
# at less than this version number, you should upgrade it from the
# main admin page.
define("REQUIRED_VERSION", "2.3.0");

# Constant: PACKAGE_URL
# The full URL of the LnBlog project home page.
define("PACKAGE_URL", "https://lnblog.skepticats.com/");

# Constant: DOCUMENTATION_URL
# Full URL of the LnBlog documentation.
define("DOCUMENTATION_URL", "https://lnblog.skepticats.com/documentation/");

# Add I18N support here, as this is currently the earliest we can do it.
# Refer to the lib/i18n.php file for details.
require_once __DIR__."/lib/i18n.php";

# Constant: PACKAGE_DESCRIPTION
# The offical text-blurb description of the software.
define("PACKAGE_DESCRIPTION", spf_("%s: a simple and (hopefully) elegant weblog", PACKAGE_NAME));

# Constant: PACKAGE_COPYRIGHT
# The offical LnBlog copyright notice.
define("PACKAGE_COPYRIGHT", _("Copyright &copy; 2005 &ndash; 2014, Peter A. Geer <pageer@skepticats.com>"));

######################################
# Section: Miscellaneous configuration
# Assorted configuration constants that don't fit into any coherent category.

# Types of files for use with the getlink() function.
# For convenience, these are defined as the directories under a theme.
define("LINK_IMAGE", "images");
define("LINK_STYLESHEET", "styles");
define("LINK_SCRIPT", "scripts");

# Constant: CACHEBUST_PARAMETER
# Defines a cache-busting parameter for static assets.  You can set this if you want,
# but the default is to use the current software version.
@define('CACHEBUST_PARAMETER', PACKAGE_VERSION);

# Constant: DEFAULT_JQUERYUI_THEME
# The default theme to use for jQuery UI.  Individual themes can override this by setting
# the JQUERYUI_THEME constant to the desired value.
@define('DEFAULT_JQUERYUI_THEME', 'smoothness');

# Constant: DEFAULT_TIME_ZONE
# Set the default time zone for use with date functions.
@define('DEFAULT_TIME_ZONE', 'America/New_York');

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

# Constant: UPLOAD_IGNORE_UNINITIALIZED
# Work around browsers that don't send form elements for undisplayed uploads.
#
# This constant works around a bug raised while testing under Konqueror.  When
# an entry was posted without ever showing the file upload box, i.e. the advanced
# posting options were never expanded, the browser did not send any form field for
# the upload field.  This resulted in an invalid field error raised by LnBlog instead
# of the expected empty file.
#
# If you get "invalid field name - upload not initiated" errors when you try to
# post an entry, but were not trying to upload a file with it, then set this to true.

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

########################################
# Section: User Authentication
# Constants that relate to user authentication

# Constant: AUTH_USE_SESSION
# Use sessions in authentication instead of just cookies.
#
# *Default* is true.
@define("AUTH_USE_SESSION", true);

# Constant: LOGIN_IP_LOCK
# Lock logins to the current IP address, so that the user will be
# logged out if the IP changes.  If false, then logins will be
# locked to the user agent instead.
#
# *Default* is false.
@define("LOGIN_IP_LOCK", false);

# Constant: FORCE_HTTPS_LOGIN
# Force all logins to go through HTTPS.  Non-https attempts will
# be redirected and/or rejected.
#
# Note that this is not yet fully implemented.
#
# *Default* is false.
@define("FORCE_HTTPS_LOGIN", false);

# Constant: BLOCK_ON_MISSING_ORIGIN
# Part of CSRF protection.  If this is true, POST requests will be denied
# if the same-site origin check fails because the origin URL could not
# be determined.  Turn this off if your server is not receiving
# "Origin" or "Referer" headers.
@define("BLOCK_ON_MISSING_ORIGIN", true);

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
@define("BLOG_DRAFT_PATH", "drafts");

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

###############################################
# Section: Blog entry and Article configuration

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
# The *default* is "entry.xml".
@define("ENTRY_DEFAULT_FILE", "entry.xml");  # Formerly current.htm

# Constant: ENTRY_DATE_FORMAT
# Format used to display dates for entries and articles.
# Note that, like <ENTRY_PATH_FORMAT> this string includes strftime()
# scancodes.
#
# The *default* is "%Y-%m-%d %H:%M %Z", which gives a date like
# "2005-08-16 04:26 EST".
@define("ENTRY_DATE_FORMAT", "%Y-%m-%d %H:%M %Z");


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

# Set the time zone for date functions.
date_default_timezone_set(DEFAULT_TIME_ZONE);

set_current_theme_name();

# Include constants for classes.
require_once __DIR__."/lib/constants.php";

# Include the library of wrapper functions, which will be used to actually
# create objects by including the right file at the right time.
# This is for parsimony of includes.
require_once __DIR__."/lib/creators.php";

# Now that everything is initialized, we can create the global event register
# and plugin manager and create a top-level page for handling output..
# TODO: Need to get rid of all the globals and switch to singleton instance calls.
# Then we can get rid of this.
require_once __DIR__."/lib/EventRegister.php";
require_once __DIR__."/lib/Page.php";
require_once __DIR__.'/lib/System.php';
require_once __DIR__."/lib/PluginManager.php";

# Initialize the plugins
PluginManager::instance()->loadPlugins();
