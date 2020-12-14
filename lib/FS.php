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
Class: FS
An abstract class for writing to the filesystem.  We use this to access the
concrete subclasses for native filesystem and FTP access.  Maybe one day
there will be some other useful method for filesystem access....
*/

abstract class FS
{

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

    # Method: getcwdLocal
    # Wrapper around native getcwd() function.  This is always the local current directory, regardless
    # of the filesystem driver.
    public function getcwdLocal() {
        return getcwd();
    }

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

    # Method: copy_rec
    # Recursively copy a directory.  If called on a file, it will just
    # copy the file.
    #
    # Parameters:
    # src  - The source directory to copy
    # dest - The destination directory
    #
    # Returns:
    # True on success, false if any part of the operation (file copy or
    # directory creation) fails.
    public function copy_rec($src, $dest) {
        $ret = true;

        if (! $this->is_dir($src)) {
            return $this->copy($src, $dest);
        }

        if (! $this->is_dir($dest)) {
            $ret = $this->mkdir($dest);
        }

        if (!$ret) {
            return $ret;
        }

        $dirhand = opendir($src);
        while ($ret && false !== ( $ent = readdir($dirhand))) {
            if ($ent == "." || $ent == "..") {
                continue;
            }
            $from_path = $src . DIRECTORY_SEPARATOR . $ent;
            $to_path = $dest . DIRECTORY_SEPARATOR . $ent;
            $ret = $this->copy_rec($from_path, $to_path);
        }
        closedir($dirhand);

        return $ret;
    }

    # Method: read_file
    # Read a file from disk.
    #
    # Parameters:
    # path - The path to the file to read.
    #
    # Returns:
    # The file contents as a string, or calse on failure.
    public function read_file($path) {
        return file_get_contents($path);
    }

    # Method: readfile
    # Wrapper around native readfile() function.
    public function readfile($path) {
        return readfile($path);
    }

    # Method: echo_to_string
    # Wrapper around the native echo statement.
    public function echo_to_output($string) {
        echo $string;
    }

    # Method: is_dir
    # Wrapper around native is_dir() function.
    public function is_dir($path) {
        return is_dir($path);
    }

    # Method: is_file
    # Wrapper around native is_file() method
    public function is_file($path) {
        return is_file($path);
    }

    # Method: is_uploaded_file
    # Wrapper around native is_uploaded_file() function.
    public function is_uploaded_file($path) {
        return is_uploaded_file($path);
    }

    # Method: file_exists
    # Wrapper around native file_exists() function.
    public function file_exists($path) {
        return file_exists($path);
    }

    # Method: scandir
    # Wraper around native scandir() function.
    public function scandir($directory, $sorting_order = SCANDIR_SORT_ASCENDING) {
        return scandir($directory, $sorting_order);
    }

    # Method: scan_directory
    # Does essentially the same thing as scandir on PHP 5.  Gets all the entries
    # in the given directory.
    #
    # Parameters:
    # path      - The directory path to scan.
    # dirs_only - *Optional* parameter to list only the directories in the path.
    #             The *default* is false.
    #
    # Returns:
    # An array of directory entry names, removing "." and ".." entries.
    public function scan_directory($path, $dirs_only=false) {
        if (! is_dir($path)) return array();
        $dirhand = opendir($path);
        $dir_list = array();
        while ( false !== ($ent = readdir($dirhand)) ) {
            if ($ent != "." && $ent != "..") {
                if (! $dirs_only) $dir_list[] = $ent;
                else if (is_dir($path.PATH_DELIM.$ent)) $dir_list[] = $ent;
            }
        }
        closedir($dirhand);
        return $dir_list;
    }

    # Method: glob
    # Does a shell wildcard glob and returns the results.
    #
    # Parameters:
    # expression = (string) The wildcard pattern to match.
    # flags - (int) A bitmask of flags to manipulate the glob behavior.
    #
    # Returns:
    # An array containing the list of matched files and/or directories.
    public function glob($expression, $flags = 0) {
        return glob($expression, $flags);
    }

    # Method: filesize
    # Wrapper around native filesize() function.
    public function filesize($path) {
        return filesize($path);
    }

    # Method: filemtime
    # Wrapper around native filemtime function.
    public function filemtime($path) {
        return filemtime($path);
    }

    # Method: file
    # Wrapper around native file function.
    public function file($path) {
        return file($path);
    }

    # Method: realpath
    # Wrapper around native realpath function.
    public function realpath($path) {
        return realpath($path);
    }

    # Method: is_link
    # Wrapper around native is_link function.
    public function is_link($path) {
        return is_link($path);
    }

    # Method: symlink
    # Wrapper around native symlink function.
    public function symlink($src, $dest) {
        return symlink($src, $dest);
    }
}
