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

class NativeFS extends FS {

	public function __construct() {
		$this->default_mode = defined("FS_DEFAULT_MODE") ? FS_DEFAULT_MODE : 0000;
		$this->script_mode = defined("FS_SCRIPT_MODE") ? FS_SCRIPT_MODE : 0000;
		$this->directory_mode = defined("FS_DIRECTORY_MODE") ? FS_DIRECTORY_MODE : 0000;;
	}
	
	
	public function __destruct() {}

	public function localpathToFSPath($path) { return $path; }
	public function FSPathToLocalpath($path) { return $path; }

	public function chdir($dir) {
		return chdir($dir);
	}
	
	public function getcwd() {
		return getcwd();
	}
	
	public function mkdir($dir, $mode=false) { 
		if (! $mode) $mode = $this->directory_mode;
		if ($mode) {
			$old_mask = umask(0000);
			$ret = mkdir($dir, $mode); 
			umask($old_mask);
		} else {
			# The optional mode assumes PHP 4.2.0 or greater.
			# Is that a problem?
			$ret = mkdir($dir);
		}
		return $ret;
	}

	public function mkdir_rec($dir, $mode=false) {
		$parent = dirname($dir);
		if ( $parent == $dir ) return false;
		if (! is_dir($parent) )	$ret = $this->mkdir_rec($parent, $mode);
		else $ret = true;
		if ($ret) $ret = $this->mkdir($dir, $mode);
		return $ret;
	}

	public function rmdir($dir) {
		# We can't delete the current directory because we're still using it.
		if ( realpath($dir) == $this->getcwd() ) {
			chdir("..");
		}
		return rmdir($dir);
	}

	public function rmdir_rec($dir) {
		if (! is_dir($dir)) return $this->delete($dir);
		$dirhand = opendir($dir);
		$ret = true;
		while ( ( false !== ( $ent = readdir($dirhand) ) ) && $ret ) {
			if ($ent == "." || $ent == "..") {
				continue;
			} else {
				$ret &= $this->rmdir_rec($dir.PATH_DELIM.$ent);
			}
		}
		closedir($dirhand);
		if ($ret) $ret &= $this->rmdir($dir);
		return $ret;	
	}

	public function chmod($path, $mode) {
		return chmod($path, $mode);
	}

	public function copy($src, $dest)   { return copy($src, $dest); }
	public function rename($src, $dest) { return rename($src, $dest); }
	public function delete($src)        { return unlink($src); }

	public function write_file($path, $contents) {
		$mask = $this->isScript($path) ? $this->script_mode : $this->default_mode;
		
		if ($mask) {
			$old_umask = umask(0000);
			umask($mask xor 0777);
		}
		
		$fh = fopen($path, "w");
		if ($fh) {
			$ret = fwrite($fh, $contents);
			fclose($fh);
		} else $ret = false;

		if ($mask) {
			umask($old_umask);
			$this->chmod($path, $mask);
		}
		
		return $ret;
	}

}