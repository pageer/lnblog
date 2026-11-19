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

    function _(string $str) {
        /** @var array<string, string> $strings */
        $strings = [];
        if (file_exists(
            INSTALL_ROOT.PATH_DELIM.
            LOCALEDIR.PATH_DELIM.LANGUAGE.".php"
        )) {
            include_once(
                INSTALL_ROOT.PATH_DELIM.LOCALEDIR.PATH_DELIM.LANGUAGE.".php"
            );
        }
        if (isset($strings[$str])) {
            return $strings[$str];
        } else {
            return $str;
        }
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
# Since all strftime() codes start with a %, old code that uses date()
# codes will be unaffected by the format conversion done here.
#
# As of PHP 8.1, strftime() itself is deprecated.  When the USE_STRFTIME
# option is on and the intl extension is loaded, dates are formatted with
# IntlDateFormatter, which provides the same locale-aware output without
# the deprecation.  Otherwise, the format codes are converted and the
# dates are formatted with date(), which loses locale-aware month and day
# names.
#
# Parameters:
# fmt - The format string for the date
# ts  - The *optional* timestamp to use for the date.
#
# Returns:
# The formatted date string.

function fmtdate($fmt, $ts=false) {
    $globals = new GlobalFunctions();
    if (!$ts) $ts = $globals->time();

    $locale = $globals->defined('LANGUAGE') ? $globals->constant('LANGUAGE') : '';
    $icu_locale = $locale ? fmtdate_icu_locale($locale) : false;
    if ($globals->constant('USE_STRFTIME') && $icu_locale &&
        class_exists('IntlDateFormatter')
    ) {
        $ret = fmtdate_intl($fmt, $ts, $icu_locale);
        if ($ret !== false) {
            return $ret;
        }
        # The format could not be converted, so fall through to date().
    }

    # Codes with no direct date() equivalent get expanded to literal values
    # first, since they depend on the timestamp.
    $fmt = fmtdate_expand_codes($fmt, $ts);

    $strftime_codes = array('%a','%A','%b','%h','%B','%c','%d','%D',
                            '%F','%H','%I','%m','%M','%n','%p',
                            '%r','%R','%S','%t','%T','%x','%X','%y',
                            '%Y','%z','%Z');
    $date_codes = array('D', 'l', 'M', 'M', 'F', 'D M j H:i:s Y', 'd', 'm/d/y',
                        'Y-m-d', 'H', 'h', 'm', 'i', "\n", 'A',
                        'h:i:s A', 'H:i', 's', "\t", 'H:i:s', 'm/d/y',
                        'H:i:s', 'y', 'Y', 'O', 'T');
    $fmt = str_replace($strftime_codes, $date_codes, $fmt);
    return date($fmt, $ts);
}

# Function: fmtdate_intl
# Format a date with IntlDateFormatter, converting strftime-style format
# codes to ICU patterns.  Returns false if the format cannot be handled,
# so the caller can fall back to date()-based formatting.

function fmtdate_intl($fmt, $ts, $locale) {
    $pattern = fmtdate_icu_pattern($fmt, $ts);
    if ($pattern === false) {
        return false;
    }
    try {
        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            date_default_timezone_get(),
            IntlDateFormatter::GREGORIAN,
            $pattern
        );
        return $formatter->format($ts);
    } catch (Throwable $e) {
        # Bad locale, pattern, or ICU error - let the caller fall back.
        return false;
    }
}

# Function: fmtdate_icu_locale
# Convert a LANGUAGE setting into a locale ID that ICU will accept.
# POSIX-locale values like "en_US.UTF-8" or "C.UTF-8" are not valid ICU
# locale IDs, so any charset or modifier is stripped, and the C/POSIX
# locales (which have no locale-specific formatting) return false so the
# caller uses the date()-based logic.

function fmtdate_icu_locale($language) {
    $locale = str_replace('-', '_', $language);
    $modifier = strpos($locale, '@');
    if ($modifier !== false) {
        $locale = substr($locale, 0, $modifier);
    }
    $charset = strpos($locale, '.');
    if ($charset !== false) {
        $locale = substr($locale, 0, $charset);
    }
    $locale = trim($locale);
    if ($locale === '' || $locale === 'C' || $locale === 'POSIX') {
        return false;
    }
    return $locale;
}

# Function: fmtdate_icu_pattern
# Convert a strftime-style format string into an ICU date pattern.
# Codes without an ICU equivalent are expanded to literal values; literal
# text is quoted as ICU requires.

function fmtdate_icu_pattern($fmt, $ts) {
    # Codes with no direct ICU equivalent get expanded to literal values
    # first, since they depend on the timestamp.
    $fmt = fmtdate_expand_codes($fmt, $ts);

    $icu_codes = array(
        '%a' => 'EEE',   '%A' => 'EEEE', '%b' => 'MMM', '%h' => 'MMM',
        '%B' => 'MMMM',  '%c' => 'EEE MMM d HH:mm:ss yyyy',
        '%d' => 'dd',    '%D' => 'MM/dd/yy', '%F' => 'y-MM-dd',
        '%H' => 'HH',    '%I' => 'hh',
        '%m' => 'MM',    '%M' => 'mm',   '%n' => "\n",  '%p' => 'a',
        '%r' => 'hh:mm:ss a', '%R' => 'HH:mm', '%S' => 'ss',
        '%t' => "\t",    '%T' => 'HH:mm:ss', '%x' => 'MM/dd/yy',
        '%X' => 'HH:mm:ss', '%y' => 'yy', '%Y' => 'yyyy',
        '%z' => 'ZZZ',   '%Z' => 'zzzz',
    );

    $pattern = '';
    $literal = '';
    $length = strlen($fmt);
    for ($i = 0; $i < $length; $i++) {
        if ($fmt[$i] == '%' && $i + 1 < $length) {
            $code = $fmt[$i].$fmt[$i + 1];
            $i++;
            if ($code == '%%') {
                $literal .= '%';
                continue;
            }
            if (isset($icu_codes[$code])) {
                $pattern .= fmtdate_quote($literal).$icu_codes[$code];
                $literal = '';
                continue;
            }
            # Unknown code - treat it as literal text.
        }
        $literal .= $fmt[$i];
    }
    return $pattern !== '' || $literal !== '' ?
        $pattern.fmtdate_quote($literal) : false;
}

# Function: fmtdate_quote
# Quote a literal string for use in an ICU date pattern.

function fmtdate_quote($literal) {
    if ($literal === '') {
        return '';
    }
    return "'".str_replace("'", "''", $literal)."'";
}

# Function: fmtdate_expand_codes
# Expand strftime codes that have no format-string equivalent into literal
# values based on the timestamp.  Used by both the IntlDateFormatter and
# date() formatting paths.

function fmtdate_expand_codes($fmt, $ts) {
    $year = (int)date('Y', $ts);
    $wday = (int)date('w', $ts);          # 0 = Sunday
    $wday_mon = ($wday + 6) % 7;          # 0 = Monday
    $yday = (int)date('z', $ts);          # 0-based day of year

    return str_replace(
        array('%C', '%e', '%G', '%g', '%j', '%U', '%u', '%V', '%w', '%W'),
        array(
            sprintf('%02d', intdiv($year, 100)),
            sprintf('%2d', (int)date('j', $ts)),
            (string)(int)date('o', $ts),
            sprintf('%02d', (int)date('o', $ts) % 100),
            sprintf('%03d', $yday + 1),
            sprintf('%02d', intdiv($yday + 7 - $wday, 7)),
            (string)((int)date('N', $ts)),
            date('W', $ts),
            (string)$wday,
            sprintf('%02d', intdiv($yday + 7 - $wday_mon, 7)),
        ),
        $fmt
    );
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
