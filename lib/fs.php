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
Class: FS
An abstract class for writing to the filesystem.  We use this to access the
concrete subclasses for native filesystem and FTP access.  Maybe one day 
there will be some other useful method for filesystem access....
*/

abstract class FS {

	public $default_mode;
	public $directory_mode;
	public $script_mode;

	public abstract function __construct();
	public abstract function __destruct();
	
	# Method: getcwd
	# Get the working directory for the class.
	#
	# Returns;
	# A string with the working directory reported by the filesystem functions.
	public abstract function getcwd();
	
	# Method: chdir
	# Change working directory
	#
	# Parameters: 
	# dir - A standard local path to the new directory.
	#
	# Returns:
	# True on success, false on failure.
	public abstract function chdir($dir);
	
	# Method: mkdir
	# Create a new directory.  
	# For this function, the immediate parent directory must exist
	#
	# Paremeters:
	# dir  - The local path to the new directory.
	# mode - An *optional* umask for file permissions on the new directory.
	#
	# Returns:
	# False on failure, an unspecified non-false value on success.
	public abstract function mkdir($dir, $mode=0777);
	
	# Method: mkdir_rec
	# Recursive version of <mkdir>.
	# This will create all non-existent parents of the target path.
	#
	# Parameters:
	# dir  - The local path to the new directory.
	# mode - An *optional* umask for file permissions on the new directory.
	#
	# Retruns:
	# False on failure, a non-false value on success.
	public abstract function mkdir_rec($dir, $mode=0777);
	
	# Method: rmdir
	# Remove an empty directory.
	#
	# Parameters:
	# dir - The local path of the directory to remove.
	#
	# Returns:
	# True on success, false on failure.
	public abstract function rmdir($dir);
	
	# Method: rmdir
	# Recursive version of <rmdir>.
	# Remove a directory and all files and directories it contains.
	#
	# Parameters:
	# dir - The local path of the directory to remove.
	#
	# Returns:
	# True on success, false on failure.
	public abstract function rmdir_rec($dir);
	
	# Method: chmod
	# Change permissions on a file or directory.
	#
	# Parameters:
	# path - The local path to the file to change.
	# mode - The UNIX octal value to set the permissions to.
	#
	# Returns:
	# False on failure, an unspecified non-false value on success.
	public abstract function chmod($path, $mode);
	
	# Method: directoryMode
	# Gets and sets the permissions to use when creating directories.
	#
	# Parameters:
	# mode - The *optional* octal permissions to use.
	#
	# Returns:
	# The octal UNIX permissions used when creating directories.
	public function directoryMode($mode=false) {
		if ($mode) $this->directory_mode = $mode;
		return $this->directory_mode;
	}

	# Method: scriptMode
	# Gets and sets the 
	#
	# Parameters:
	# mode - The *optional* octal permissions to use.
	#
	# Returns:
	# The octal UNIX permissions used when creating PHP scripts.
	public function scriptMode($mode=false) {
		if ($mode) $this->script_mode = $mode;
		return $this->script_mode;
	}

	# Method: defaultMode
	# Gets and sets the permissions to use for other files, i.e. not
	# directories or PHP scripts
	#
	# Parameters:
	# mode - The *optional* octal permissions to use.
	#
	# Returns:
	# The octal UNIX permissions used when creating other files.
	public function defaultMode($mode=false) {
		if ($mode) $this->default_mode = $mode;
		return $this->default_mode;
	}

	# Method: isScript
	# Determines if a given path represents a PHP script or not.
	#
	# Parameters:
	# path - the path of the file in question.
	#
	# Returns:
	# True if the file uses a known extension for PHP scripts, false otherwise.
	# Currently, known extensions are of the form .phpX where X is empty or a 
	# number.
	public function isScript($path) {
		$filedata = pathinfo($path);
		if ( isset($filedata['extension']) ) {
			$ext = strtolower($filedata['extension']);
			if ( substr($ext, 0, 3) == "php" &&
			    ( strlen($ext) == 3 || is_numeric(substr($ext, 3, 1)) ) ) {
				return true;
			}
		}
		return false;
	}

	# Method: copy
	# Copy a single file.
	#
	# Parameters:
	# src  - The local path to the file to copy 
	# dest - The path to copy it to.
	#
	# Returns:
	# True on success, false on failure.
	public abstract function copy($src, $dest);
	
	# Method: rename
	# Move or rename a file.
	#
	# Parameters:
	# src  - The local path to the file to move or rename.
	# dest - The new file path.
	#
	# Returns:
	# True on success, false on failure.
	public abstract function rename($src, $dest);
	
	# Method: delete
	# Delete a single file.
	#
	# Parameters:
	# src - The local path to the file to delete.
	#
	# Returns:
	# True on success, false on failure.
	public abstract function delete($src);
	
	# Method: write_file
	# Write a string to a new text file.
	# 
	# Parameters:
	# path     - The local path to the file.
	# contents - A string containing te desired contents of the file.
	#
	# Returns:
	# False on failure, an unspecified non-false value on success.
	public abstract function write_file($path, $contents);

}
?>
