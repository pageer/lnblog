<?php

class Path {

    const WINDOWS_SEP = '\\';
    const UNIX_SEP = '/';

    public static $sep = DIRECTORY_SEPARATOR;
    public $path = array();
    public $dirsep = DIRECTORY_SEPARATOR;

    public function __construct() {
        $this->path = func_get_args();
        $this->dirsep = self::$sep;
    }

    public static function mk($arr=null) {
        $p = new Path();
        if (is_array($arr)) {
            $p->path = $arr;
        } else {
            $p->path = func_get_args();
        }
        return $p->getPath();
    }

    public function isWindows() {
        return self::$sep === self::WINDOWS_SEP;
    }

    public function isAbsolute($path) {
        switch($this->dirsep) {
            case self::WINDOWS_SEP:
                $c1 = ord(strtoupper(substr($path,0,1)));
                $c2 = substr($path,1,1);
                return ( $c1 >= ord("A") && $c1 <= ord("Z") && $c2 == ':');
            case self::UNIX_SEP:
                return substr($path,0,1) == self::UNIX_SEP;
        }
    }

    public static function implodePath($sep, $path) {
        $path = array_filter($path);
        $ret = implode($sep, $path);
        return str_replace($sep.$sep, $sep, $ret);
    }

    public function getPath() {
        return self::implodePath($this->dirsep, $this->path);
    }

    public static function get() {
        $args = func_get_args();
        return self::implodePath(self::$sep, $args);
    }

    public function getCanonical() {
        $str = $this->getPath();
        $components = explode($this->dirsep, $str);
        $ret = '';

        for ($i = count($components) - 1; $i > 0; $i--) {
            switch ($components[$i]) {
                case ''   : break;
                case '.'  : break;
                case '..' : $i--; break;
                default   : $ret = $this->dirsep.$components[$i].$ret;
            }
        }

        # Note that for UNIX platforms, $components[0] will be empty for
        # absolute paths.
        if ($components[0] != '.' && $components[0] != '..') {
            $ret = $components[0].$ret;
        }

        return $ret;
    }

    public function urlSlashes($striproot=false) {
        $path = $this->getPath();
        if ($striproot && strpos($path,$striproot) === 0) {
            $path = substr($path, strlen($striproot));
        }

        if ($this->dirsep != '/') {
            if ($this->isAbsolute($path)) $path = substr($path, 2);
            $path = str_replace($this->dirsep, '/', $path);
        }
        return $path;
    }

    public function stripPrefix($prefix) {
        $ret = $this->getPath();
        if ($this->hasPrefix($prefix)) $ret = substr($ret, strlen($prefix));
        return $ret;
    }

    public function hasPrefix($prefix) {
        return strpos($this->getPath(), $prefix) === 0;
    }

    public function append($dir) {
        $args = func_get_args();
        $this->path = array_merge($this->path, $args);
    }
}
