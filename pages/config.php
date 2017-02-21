<?php
$instdir = dirname(getcwd());
if (! defined('INSTALL_ROOT')) {
	define("INSTALL_ROOT", $instdir);
}
if (! defined("PATH_SEPARATOR") ) {
	define("PATH_SEPARATOR", strtoupper(substr(PHP_OS,0,3)=='WIN')?';':':');
}
require_once INSTALL_ROOT.DIRECTORY_SEPARATOR."blogconfig.php";
