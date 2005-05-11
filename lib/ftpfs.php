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

class FTPFS extends FS {
	
	var $host;
	var $username;
	var $connection;
	var $status;
	
	function FTPFS($host=false, $user=false, $pass=false) {
		$this->default_mode = 0755;
		if ($host) $this->host = $host;
		else $this->host = FTPFS_HOST;
		if ($user) $this->username = $user;
		else $this->username = FTPFS_USER;
		if (!$pass) $pass = FTPFS_PASSWORD;
		$this->connection = ftp_connect($this->host);
		if ($this->connection !== false) {
			$this->status = ftp_login($this->connection, $this->username, $pass);
		} else $this->status = false;
	}

	# A non-tranparent destructor.  Needed to cleanly disconnect from the 
	# FTP server.

	function desctuct() {
		$this->disconnect();
	}

	# These functions convert between local paths and FTP paths.  They depend 
	# on the FTP_ROOT constant, which is defined when the FTP configuration
	# is done and contains the full local path to the FTP root directory.
	# It defaults to two levels above the INSTALL_ROOT, i.e. it 
	# assumes that the web root is one level below the FTP root.

	function localpathToFSPath($path) {
		$char1 = substr($path, 0, 1);
		$char2 = substr($path, 1, 1);
		if ($char1 != '/' && $char2 != ':') $path = getcwd().PATH_DELIM.$path;
		$ret = substr_replace($path, '', 0, strlen(FTP_ROOT) );
		if (PATH_DELIM != '/') $ret = str_replace(PATH_DELIM, '/', $ret);
		if ( substr($ret, 0, 1) != '/' ) $ret = '/'.$ret;
		if ( defined("FTPFS_PATH_PREFIX") ) $ret = FTPFS_PATH_PREFIX.$ret;
		return $ret;
	}

	function FSPathToLocalpath($path) {
		$ret = $path;
		if ( PATH_DELIM != '/' ) $ret = substr_replace(PATH_DELIM, '/', $ret);
		$ret = realpath(FTP_ROOT.PATH_DELIM.$ret);
		return $ret;
	}

	function connected() {
		if ($this->status) $ret = ($this->connection !== false);
		else $ret = false;
		return $ret;
	}

	function reconnect($password=false) {
		if (!$password) $password = FTPFS_PASSWORD;
		if ($this->connected() ) ftp_close($this->connection);
		$this->connection = ftp_connect($this->host, $this->username, $pass);
		if ($this->connection !== false) {
			$this->status = ftp_login($this->connection, $this->username, $pass);
		} else $this->status = false;
		return $this->status;
	}

	function disconnect() {
		ftp_close($this->connection);
	}
	
	function getcwd() {
		if ($this->connected() ) $ret = ftp_pwd($this->connection);
		else $ret = false;
		$ret = $this->FSPathToLocalpath($ret);
		return $ret;
	}
	
	function chdir($dir) {
		$dir = $this->localpathToFSPath($dir);
		if ($this->connected() ) $ret = ftp_chdir($this->connection, $dir);
		else $ret = false;
		return $ret;
	}
	
	function mkdir($dir, $mode=0755) {
		$dir = $this->localpathToFSPath($dir);
		if ($this->connected() ) $ret = ftp_mkdir($this->connection, $dir);
		else $ret = false;
		return $ret;
	}

	function mkdir_rec($dir, $mode=0755) {
		if (! $this->connected() ) return false;
		$parent = dirname($dir);
		if ($parent == $dir) return false;
		if (! is_dir($parent) ) $ret = $this->mkdir_ret($dir);
		else $ret = true;
		if ($ret) $ret = $this->mkdir($dir, $mode);
		return $ret;
	}

	function chmod($path, $mode) {
		if (! $this->connected() ) return false;
		$path = $this->localpathToFSPath($path);
		$ret = ftp_chmod($path);
		return $ret;
	}

	function defaultMode() {
		return $this->default_mode;
	}

	function copy($src, $dest) {
		if (! $this->connected() ) return false;

		$ftp_dest = $this->localpathToFSPath($dest);

		$ret = false;
		if (is_file($src)) {
			$fh = fopen($src, "rb");
			if ($fh !== false)
				$ret = ftp_fput($this->connection, $ftp_dest, $fh, FTP_BINARY);
		} 
		return $ret;
	}

	function rename($src, $dest) {
		if (! $this->connected() ) return false;
		$ftp_src = $this->localpathToFSPath($src);
		$ftp_dest = $this->localpathToFSPath($dest);
		$ret = ftp_rename($this->connection, $ftp_src, $ftp_dest);
		return $ret;
	}

	function delete($src) {
		if (! $this->connected() ) return false;
		$ftp_src = $this->localpathToFSPath($src);
		$ret = ftp_delete($this->connection, $ftp_src);
		return $ret;
	}

	function write_file($path, $contents) {
		if (! $this->connected() ) return false;
		$temp = tmpfile();
		$ret = fwrite($temp, $contents);
		$ftp_path = $this->localpathToFSPath($path);
		if ($ret !== false) {
			$ret = fseek($temp, 0);
			if ($ret != -1) {
				$ret = ftp_fput($this->connection, $ftp_path, $temp, FTP_BINARY);
			}
		}
		return $ret;
	}

}

?>
