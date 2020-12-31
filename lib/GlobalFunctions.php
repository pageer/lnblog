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

    public function imagecreatefromjpeg(string $filename) {
        return imagecreatefromjpeg($filename);
    }

    public function imagecreatefrompng(string $filename) {
        return imagecreatefrompng($filename);
    }

    public function imagesx($image) {
        return imagesx($image);
    }

    public function imagesy($image) {
        return imagesy($image);
    }

    public function imagescale($image, int $new_width, int $new_height = -1, int $mode = IMG_BILINEAR_FIXED) {
        return imagescale($image, $new_width, $new_height, $mode);
    }

    public function imagejpeg($image, $to = null, int $quality = -1) {
        return imagejpeg($image, $to, $quality);
    }

    public function imagepng($image, $to = null, int $quality = -1, int $filters = -1) {
        return imagepng($image, $to, $quality, $filters);
    }

    public function getMimeTYpe(string $path): string {
        if (extension_loaded("fileinfo")) {
            $mh = finfo_open(FILEINFO_MIME|FILEINFO_PRESERVE_ATIME);
            $ret = finfo_file($mh, $path);
        } elseif (function_exists('mime_content_type')) {
            $ret =  mime_content_type($path);
        } else {
            # No fileinfo, no mime_magic, so revert to file extension matching.
            # This is a dirty and incomplete method, but I suppose it's better
            # than nothing.  Though only marginally.
            require_once __DIR__.'/stupid_mime.php';
            $ret = stupid_mime_get_type($path);
        }
        if ($pos = strpos($ret, ';')) {
            $ret = substr($ret, 0, $pos);
        }
        return $ret;
    }
}
