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

require_once("utils.php");

define("FILEUPLOAD_NO_ERROR", 0);
define("FILEUPLOAD_NO_FILE", 1);
define("FILEUPLOAD_FILE_EMPTY", 2);
define("FILEUPLOAD_SERVER_TOO_BIG", 3);
define("FILEUPLOAD_FORM_TOO_BIG", 4);
define("FILEUPLOAD_PARTIAL_FILE", 5);
define("FILEUPLOAD_NOT_UPLOADED", 6);
define("FILEUPLOAD_NAME_TRUNCATED", 7);
define("FILEUPLOAD_BAD_NAME", 8);

class FileUpload {
	
	#var $file;
	var $destdir;
	var $destname;
	var $tempname;
	var $size;
	var $mimetype;
	var $error;

	function FileUpload($field, $dir=false, $index=false) {
		if (!$dir) $this->destdir = getcwd();
		if (php_version_at_least("4.1.0")) {
			$this->error = true;
			if ($index !== false) {
				#$this->file = $_FILES[$field];
				$this->destname = $_FILES[$field]['name'];
				$this->tempname = $_FILES[$field]['tmp_name'];
				$this->size = $_FILES[$field]['size'];
				$this->mimetype = $_FILES[$field]['type'];
				if (php_version_at_least("4.2.0")) 
					$this->error = $_FILES[$field]['error'];
			} else {
				#$this->file = $_FILES[$field][][$index]
				$this->destname = $_FILES[$field]['name'][$index];
				$this->tempname = $_FILES[$field]['tmp_name'][$index];
				$this->size = $_FILES[$field]['size'][$index];
				$this->mimetype = $_FILES[$field]['type'][$index];
				if (php_version_at_least("4.2.0")) 
					$this->error = $_FILES[$field]['error'][$index];
			}
		} else {
			global $HTTP_POST_FILES;
			if ($index === false) {
				#$this->file = $HTTP_POST_FILES[$field];
				$this->destname = $HTTP_POST_FILES[$field]['name'];
				$this->tempname = $HTTP_POST_FILES[$field]['tmp_name'];
				$this->size = $HTTP_POST_FILES[$field]['size'];
				$this->mimetype = $HTTP_POST_FILES[$field]['type'];
			} else {
				#$this->file = $HTTP_POST_FILES[$field][][$index]
				$this->destname = $HTTP_POST_FILES[$field]['name'][$index];
				$this->tempname = $HTTP_POST_FILES[$field]['tmp_name'][$index];
				$this->size = $HTTP_POST_FILES[$field]['size'][$index];
				$this->mimetype = $HTTP_POST_FILES[$field]['type'][$index];
			}
			$this->error = true;
		}
	}

	function status() {

		if (php_version_at_least("4.0.2")) {
			if (is_uploaded_file())
		}
	
		if (php_version_at_least("4.2.0")) {
			switch ($this->error) {
				case UPLOAD_ERR_OK:
					$ret = FILEUPLOAD_NO_ERROR;
					break;
				case UPLOAD_ERR_INI_SIZE:
					$ret = FILEUPLOAD_SERVER_TOO_BIG;
					break;
				case UPLOAD_ERR_FORM_SIZE:
					$ret = FILEUPLOAD_FORM_TOO_BIG;
					breal;
				case UPLOAD_ERR_PARTIAL:
					$ret = FILEUPLOAD_PARTIAL_FILE;
					break;
				case UPLOAD_ERR_NO_FILE:
					$ret = FILEUPLOAD_NO_FILE;
					break;
			}
		}
		if ($ret == FILEUPLOAD_NO_ERROR && $this->size <= 0) 
			$ret = FILEUPLOAD_FILE_EMPTY;
		# Simple filename truncation test.  Returns an error if there is 
		# no dot in the filename.
		if ( $ret == FILEUPLOAD_NO_ERROR && ! strstr($this->destname, ".") )
			$ret = FILEUPLOAD_NAME_TRUNCATED;
			
		if (php_version_at_least("4.0.3")) {
			$is_up = is_uploaded_file($this->tempname);
		} else {
			if (!$tmp_file = get_cfg_var('upload_tmp_dir')) {
				$tmp_file = dirname(tempnam('', ''));
			}
			$tmp_file .= '/' . basename($filename);
			/* User might have trailing slash in php.ini... */
			$is_up = (ereg_replace('/+', '/', $tmp_file) == $filename);
		}
		if (! $is_up) $ret = FILEUPLOAD_BAD_NAME;
		return $ret;
	}

	function completed() { 
		return ($this->status() == FILEUPLOAD_NO_ERROR); 
	}

	function moveFile() {
		if (php_version_at_least("4.0.3")) {
			$ret = move_uploaded_file($this->tempname, $this->destdir.PATH_DELIM.$this->destname);
		} else {

		}
	}

}

?>
