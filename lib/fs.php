<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005 Peter A. Geer <pageer@skepticats.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

# An abstract class for writing to the filesystem.  We use this to access the
# concrete subclasses for native filesystem and FTP access.  Maybe one day 
# there will be some other useful method for filesystem access....

require_once("blogconfig.php");

class FS {

	var $default_mode;

	function FS() {}
	function destruct() {}
	function getcwd() {}
	function chdir($dir) {}
	function mkdir($dir, $mode=0777) {}
	function mkdir_rec($dir, $mode=0777) {}
	function chmod($path, $mode) {}
	function defaultMode() {}
	function copy($src, $dest) {}
	function rename($src, $dest) {}
	function write_file($path, $contents) {}

}

if ( file_exists(INSTALL_ROOT.PATH_DELIM.FS_PLUGIN_CONFIG) ) {
	require_once(FS_PLUGIN_CONFIG);
} else {
	if (!defined("FS_PLUGIN")) define("FS_PLUGIN", "nativefs");
}
require_once(FS_PLUGIN.".php");

function CreateFS() {
	switch (FS_PLUGIN) {
		case "nativefs":
			return new NativeFS;
		case "ftpfs":
			return new FTPFS;
		default:
			return new NativeFS;
	}
}

?>
