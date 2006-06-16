<?php

# File: i18n.php
# Support functions for internationalization.
#
# This file activates support for the GNU gettext extension if it is loaded
# and falls back to an ad hoc system using native PHP functions otherwise.

if (extension_loaded("gettext")) {
	setlocale(LC_ALL, LANGUAGE);
	bindtextdomain(PACKAGE_NAME, INSTALL_ROOT.PATH_DELIM.LOCALEDIR);
	textdomain(PACKAGE_NAME);
} else {

	# Function: _
	# Mimics the behavior of gettext.
	#
	# Parameters:
	# str - The string to translate.
	#
	# Returns:
	# The translated string.  If no translated version is found for the
	# current language, then the str parameter is returned.
	
	function _($str) {
		if (file_exists(INSTALL_ROOT.PATH_DELIM.
		                LOCALEDIR.PATH_DELIM.LANGUAGE.".php")) {
			include_once(LOCALEDIR."/".LANGUAGE.".php");
		}
		if (isset($strings[$str])) return $strings[$str];
		else return $str;
	}
}

# Function: fmtdate
# Print a formatted date.
#
# This function exists because strftime prints localized strings using the
# local character set.  So, for example, if you are running on Windows and
# set the locale to "rus" (Russian"), then you get a the Windows Russian 
# date, which is in whatever 2-byte encoding Windows uses for Russian.
# Needless to say, this breaks the page.
#
# The ugly, hacky solution is to offer the user the alternative of using
# strftime, which respects the locale and hence causes problems, or date, 
# which is stupid and ignores the locale.
#
# Parameters:
# fmt - The format string for the date
# ts  - The *optional* timestamp to use for the date.
#
# Returns:
# The formatted date string.

function fmtdate($fmt, $ts=false) {
	if (!$ts) $ts = time();
	if (USE_STRFTIME) {
		$ret = strftime($fmt, $ts);
		if (defined("SYSTEM_CHARSET") && extension_loaded("iconv")) {
			$conv = iconv(SYSTEM_CHARSET, DEFAULT_CHARSET, $ret);
			if ($conv) $ret = $conv;
		}
		return $ret;
	} else {
		#$strftime_codes = array('%a','%A','%b','%B','%c','%C','%d','%D','%e','%g',
		#                        );
		#$date_codes = array('l', 'l', 'M', 'F', 'c', 'Y', 'd', 'm/d/y', 'j', 'Y',
		#                    );
		return date($fmt, $ts);
	}
}

# Function: p_
# A convenience funtion to print a translated string.
#
# Parameters:
# str - The string to translate.

function p_($str) {
	echo _($str);
}

# Function: pf_
# A convenience function that mixes translation with printf.
# 
# Parameters:
# Takes a variable list of parameters, the first of which is a format
# string for printf, which will be translated.  The remainder are the 
# substitution variables for printf.

function pf_() {
	$args = func_get_args();
	$args[0] = _($args[0]);
	call_user_func_array("printf", $args);
}

# Function: spf_
# A convenience function that mixes translation with sprintf.
# 
# Parameters:
# Takes a variable list of parameters, the first of which is a format
# string for printf, which will be translated.  The remainder are the 
# substitution variables for printf.
#
# Returns:
# The first argument, translated with the the other arguments substituted
# for the appropriate scancodes.
function spf_() {
	$args = func_get_args();
	$args[0] = _($args[0]);
	return call_user_func_array("sprintf", $args);
}

?>
