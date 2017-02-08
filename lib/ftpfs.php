<?php
/*
    LnBlog - A simple file-based weblog focused on design elegance.
    Copyright (C) 2005-2011 Peter A. Geer <pageer@skepticats.com>

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
Class: FTPFS
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
	
	public $host = '';
	public $username = '';
	public $connection = null;
	public $status = false;
	public $ftp_root = '';
	
	public function __construct($host=false, $user=false, $pass=false) {
		$this->default_mode = FS_DEFAULT_MODE;
		$this->script_mode = FS_SCRIPT_MODE;
		$this->directory_mode = FS_DIRECTORY_MODE;

		if ($host) $this->host = $host;
		elseif (defined("FTPFS_HOST")) $this->host = FTPFS_HOST;
		if ($user) $this->username = $user;
		elseif (defined("FTPFS_USER")) $this->username = FTPFS_USER;
		if (!$pass && defined("FTPFS_PASSWORD")) $pass = FTPFS_PASSWORD;

		if (defined("FTP_ROOT")) $this->ftp_root = FTP_ROOT;
		else $this->ftp_root = "";
	
		$colon_pos = strpos(":", $this->host);
		if ($colon_pos > 0) {
			$this->connection = ftp_connect(substr($this->host, 0, $colon_pos), 
			                                substr($this->host, $colon_pos+1));
		} else {
			$this->connection = ftp_connect($this->host);
		}

		if ($this->connection !== false) {
			$this->status = ftp_login($this->connection, $this->username, $pass);
		} else $this->status = false;
		
	}

	# A non-tranparent destructor.  Needed to cleanly disconnect from the 
	# FTP server.

	public function __destruct() {
		$this->disconnect();
	}

	# These functions convert between local paths and FTP paths.  They depend 
	# on a correct FTP_ROOT, which is defined when the FTP configuration
	# is done and contains the full local path to the FTP root directory.
	# It defaults to two levels above the INSTALL_ROOT, i.e. it 
	# assumes that the web root is one level below the FTP root.

	public function localpathToFSPath($path) {
		$char1 = substr($path, 0, 1);
		$char2 = substr($path, 1, 1);
		# Check if $path is relative.
		if ($char1 != '/' && $char2 != ':') $path = getcwd().PATH_DELIM.$path;
		# Since $path should be absolute now, strip the leading FTP_ROOT part.
		$ret = substr($path, strlen($this->ftp_root) );
		# Convert native path delimiters to '/'
		if (PATH_DELIM != '/') $ret = str_replace(PATH_DELIM, '/', $ret);
		# If we end up with a relative path, add a leading '/'
		if ( substr($ret, 0, 1) != '/' ) $ret = '/'.$ret;
		# Add the prefix, if applicable.
		if ( defined("FTPFS_PATH_PREFIX") ) $ret = FTPFS_PATH_PREFIX.$ret;
		return $ret;
	}

	public function FSPathToLocalpath($path) {
		$ret = $path;
		if ( PATH_DELIM != '/' ) $ret = substr_replace(PATH_DELIM, '/', $ret);
		$ret = realpath($this->ftp_root.PATH_DELIM.$ret);
		return $ret;
	}

	public function connected() {
		if ($this->status) $ret = ($this->connection !== false);
		else $ret = false;
		return $ret;
	}

	public function reconnect($password=false) {
		if (!$password) $password = FTPFS_PASSWORD;
		if ($this->connected() ) ftp_close($this->connection);

		$colon_pos = strpos(":", $this->host);
		if ($colon_pos > 0) {
			$this->connection = ftp_connect(substr($this->host, 0, $colon_pos), 
			                                substr($this->host, $colon_pos+1));
		} else {
			$this->connection = ftp_connect($this->host);
		}
		
		if ($this->connection !== false) {
			$this->status = ftp_login($this->connection, $this->username, $pass);
		} else $this->status = false;
		return $this->status;
	}

	public function disconnect() {
		ftp_close($this->connection);
	}
	
	public function getcwd() {
		if ($this->connected() ) $ret = ftp_pwd($this->connection);
		else $ret = false;
		$ret = $this->FSPathToLocalpath($ret);
		return $ret;
	}
	
	public function chdir($dir) {
		$dir = $this->localpathToFSPath($dir);
		if ($this->connected() ) $ret = ftp_chdir($this->connection, $dir);
		else $ret = false;
		return $ret;
	}
	
	public function mkdir($dir, $mode=false) {
		if (! $mode) $mode = $this->directory_mode;
		$dir = $this->localpathToFSPath($dir);
		if ($this->connected() ) {
			$ret = ftp_mkdir($this->connection, $dir);
			if ($mode) {
				$ret = $this->chmod($dir, $mode);
			}
		} else $ret = false;
		return $ret;
	}

	public function mkdir_rec($dir, $mode=false) {
		if (! $this->connected() ) return false;
		if (! $mode) $mode = $this->directory_mode;
		$parent = dirname($dir);
		if ($parent == $dir) return false;
		if (! is_dir($parent) ) $ret = $this->mkdir_rec($dir);
		else $ret = true;
		if ($ret) $ret = $this->mkdir($dir, $mode);
		return $ret;
	}

	public function rmdir($dir) {
		# This condition accounts for the possibility that we are trying to 
		# delete the current directory with history saving disabled.  
		# We need to chdir() before doing that because the current directory 
		# is, by definition, in use.
		if ( realpath($dir) == getcwd() ) {
			chdir("..");
		}
		$dir = $this->localpathToFSPath($dir);
		if ($this->connected() ) $ret = ftp_rmdir($this->connection, $dir);
		else $ret = false;
		return $ret;
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
		closedir($dirhand);  # Close $dirhand before trying to delete it.
		if ($ret) $ret &= $this->rmdir($dir);
		return $ret;	
	}

	public function chmod($path, $mode) {
		if (! $this->connected() ) return false;
		$path = $this->localpathToFSPath($path);
		if (function_exists(ftp_chmod)) {
			$ret = ftp_chmod($this->connection, $mode, $path);
		} else {
			$ret = ftp_site($this->connection, "CHMOD $mode $path");
		}
		return $ret;
	}

	public function copy($src, $dest) {
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

	public function rename($src, $dest) {
		if (! $this->connected() ) return false;
		$ftp_src = $this->localpathToFSPath($src);
		$ftp_dest = $this->localpathToFSPath($dest);
		$ret = ftp_rename($this->connection, $ftp_src, $ftp_dest);
		return $ret;
	}

	public function delete($src) {
		if (! $this->connected() ) return false;
		$ftp_src = $this->localpathToFSPath($src);
		$ret = ftp_delete($this->connection, $ftp_src);
		return $ret;
	}

	public function write_file($path, $contents) {
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

		if ($ret) {
			$mode = $this->isScript($ftp_path) ? $this->script_mode :
			                                     $this->default_mode;
			if ($mode) {
				$this->chmod($ftp_path, $mode);
			}
		}

		return $ret;
	}

}
