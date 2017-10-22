<?php
###############################################################################
# Class: URI
# Represents a URI.  This auto-detects the URI settings from the environment,
# such as the host, current path, etc., and allows the caller to override them
# to create other URIs.  URI strings should be built using this class.
###############################################################################

class URI extends LnBlogObject {
    function __construct() {
        $this->protocol = 'http';
        $this->host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
        $this->port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
        $this->query_string = (! empty($_GET)) ? $_GET : array();
        $this->fragment = '';

        if (isset($_SERVER["REQUEST_URI"])) {
            $this->path = substr($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], '?'));
        } else {
            $this->path = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
        }

    }

    function getQueryString($sep='&') {
        $qs = '';
        if (! empty($this->query_string)) {
            $pairs = array();
            foreach ($this->query_string as $key=>$val) $pairs[] ="$key=".urlencode($val);
            $qs = "?".implode($sep, $pairs);
        }
        return $qs;
    }

    function getFragment() {
        if ($this->fragment) return "#".$this->fragment;
        else return '';
    }

    function getPort() {
        if ($this->port != 80) return ":".$this->port;
        else return '';
    }

    # Method: get
    # Gets a string containingthe URI in the canonical format.
    function get() {
        return $this->protocol."://".
               $this->host.
               $this->getPort().
               $this->path.
               $this->getQueryString().
               $this->getFragment();
    }

    # Method: getRelative
    # Gets a root-relative URI, i.e. no host, port, or protocol.
    function getRelative() {
        return $this->path.
               $this->getQueryString().
               $this->getFragment();
    }

    # Method: getPrintable
    # Like <get>, but returns a printable URI, i.e. with ampersands escaped.
    function getPrintable() {
        return $this->protocol."://".
            $this->host.
            $this->getPort().
            $this->path.
            $this->getQueryString('&amp;').
            $this->getFragment();
    }

    # Method: getRelativePrintable
    # Like <getPrintable>, but uses a relative URI.
    function getRelativePrintable() {
        return $this->path.
            $this->getQueryString('&amp;').
            $this->getFragment();
    }
    # Method: appendPath
    # Appends a path componend to the URI path, adding or removing terminal/beginning
    # slashes as appropriate.
    #
    # Parameters:
    # path - The path to append.
    function appendPath($path) {
        if ( substr($path, 0, 1) == "/") $path = substr($path, 1);
        if ( substr($this->path, -1) != "/") $this->path .= "/";
        $this->path .= $path;
    }

    # Method: clearQueryString
    # Empties the query string;
    function clearQueryString() {
        $this->query_string = array();
    }

    # Method: addQuery
    # Adds a parameter to the URI query string.
    #
    # Parameters:
    # key - The parameter name.
    # val - The value for that parameter.
    function addQuery($key, $val) {
        $this->query_string[$key] = $val;
    }

    # Method: secure
    # Set or get whether to use a secure, i.e. https, URL.
    function secure($val='') {
        if ($val === '') return $this->protocol == 'https';
        else {
            if ($this->protocol == 'https') $this->protocol = 'http';
            else $this->protocol = 'https';
            return $this->protocol;
        }
    }
}
