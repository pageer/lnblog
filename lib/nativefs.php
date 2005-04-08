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

/*
 This is a class for doing file operations using the FTP interface.  The 
 purpose for this is to keep sane file permissions on the software-generated
 content.  The FTP interface can create files and directories owned by the 
 user, while the normal local file creation functions will create files 
 owned by the web server process.
 This class uses local file operations for reading files and uses FTP
 operations for writing.  This implies three complications:
  1) The user must have have FTP access to the web root.
  2) The PHP FTP extension must be available.
  3) We must somehow correlate the local path to the FTP path.
*/

require_once("blogconfig.php");

class NativeFS extends FS {

	function NativeFS() {
		$this->default_mode = 0777;
	}
	
	function destruct() {}

	function localpathToFSPath($path) { return $path; }
	function FSPathToLocalpath($path) { return $path; }

	function chdir($dir) { return chdir($dir); }
	
	function mkdir($dir, $mode=0777) { 
		$old_mask = umask(0000);
		$ret = mkdir($dir, $mode); 
		umask($old_mask);
		return $ret;
	}

	function mkdir_rec($dir, $mode=0777) {
		$parent = dirname($dir);
		if ( $parent == $dir ) return false;
		$old_mask = umask(0000);
		if (! is_dir($parent) )	$ret = mkdir_rec($parent, $mode);
		else $ret = true;
		if ($ret) $ret = mkdir($dir, $mode);
		umask($old_mask);
		return $ret;
	}

	function chmod($path, $mode) {
		return chmod($path, $mode);
	}

	function defaultMode() {
		return $this->default_mode;
	}

	function copy($src, $dest)   { return copy($src, $dest); }
	function rename($src, $dest) { return rename($src, $dest); }
	function delete($src)        { return unlink($src); }

	function write_file($path, $contents) {
		$fh = fopen($path, "w");
		if ($fh) {
			$ret = fwrite($fh, $contents);
			fclose($fh);
		} else $ret = false;
		return $ret;
	}

}
?>
