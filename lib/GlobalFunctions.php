<?php

# Class: GlobalFunctions
# A wrapper class for global functions in the PHP standard library.
# This exists mostly to facilitate unit testing.
class GlobalFunctions {
    public function setcookie($name, $value = "", $expires = 0, $path = '', $domain = '', $secure = false, $http_only = false) {
        setcookie($name, $value, $expires, $path, $domain, $secure, $http_only);
    }

    public function include($path) {
        return include $path;
    }

    public function constant($name) {
        return constant($name);
    }

    public function defined($name) {
        return defined($name);
    }

    public function time() {
        return time();
    }

    public function mail($to, $subject, $message, $additional_headers = '', $additional_parameters = '') {
        return mail($to, $subject, $message, $additional_headers, $additional_parameters);
    }
}
