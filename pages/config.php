<?php
$instdir = dirname(getcwd());
if (! defined('INSTALL_ROOT')) define("INSTALL_ROOT", $instdir);
if (! defined("PATH_SEPARATOR") ) define("PATH_SEPARATOR", strtoupper(substr(PHP_OS,0,3)=='WIN')?';':':');
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$instdir);
require_once("blogconfig.php");
