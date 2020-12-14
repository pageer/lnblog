<?php

# Class: GlobalFunctions
# A wrapper class for global functions in the PHP standard library.
# This exists mostly to facilitate unit testing.
class GlobalFunctions
{
    public function setcookie($name, $value = "", $expires = 0, $path = '', $domain = '', $secure = false, $http_only = false) {
        setcookie($name, $value, $expires, $path, $domain, $secure, $http_only);
    }

    # Method: include
    # This is a wrapper around the native "include" construct.
    #
    # In addition to returning the result of the inclusion, this function takes a 
    # reference parameter that returns a hash of all variables defined in the 
    # current scope.  This is because we sometimes want access to any variables that
    # the include file defines, but by calling the include within this wrapper they
    # become local to the wrapper.  This reference variables gives us a way to pass
    # those values back to the caller.
    public function include($path, &$global_include_wrapper_defined_vars = null) {
        $global_include_wrapper_result = include $path;
        $global_include_wrapper_defined_vars = get_defined_vars();
        unset($global_include_wrapper_defined_vars['global_include_wrapper_defined_vars']);
        unset($global_include_wrapper_defined_vars['global_include_wrapper_result']);
        return $global_include_wrapper_result;
    }

    public function constant($name) {
        return constant($name);
    }

    public function defined($name) {
        return defined($name);
    }

    public function time($use_datetime = false) {
        if ($use_datetime) {
            $datetime = new DateTime();
            return $datetime->getTimestamp();
        }
        return time();
    }

    public function sleep($seconds) {
        sleep($seconds);
    }

    public function mail($to, $subject, $message, $additional_headers = '', $additional_parameters = '') {
        return mail($to, $subject, $message, $additional_headers, $additional_parameters);
    }
}
