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
define("FILEUPLOAD_NOT_INITIALIZED", 9);

class FileUpload {
	
	var $field;
	var $destdir;
	var $destname;
	var $tempname;
	var $size;
	var $mimetype;
	var $error;

	function FileUpload($field, $dir=false, $index=false) {
		if (!$dir) $this->destdir = getcwd();
		$this->field = $field;
		$this->destname = '';
		$this->tempname = '';
		$this->size = 0;
		$this->mimetype = '';
		$this->error = FILEUPLOAD_NOT_INITIALIZED;
		if (php_version_at_least("4.1.0")) {
			if (isset($_FILES[$field])) {
				if ($index === false) {
					$this->destname = $_FILES[$field]['name'];
					$this->tempname = $_FILES[$field]['tmp_name'];
					$this->size = $_FILES[$field]['size'];
					$this->mimetype = $_FILES[$field]['type'];
					if (php_version_at_least("4.2.0")) 
						$this->error = $_FILES[$field]['error'];
				} else {
					$this->destname = $_FILES[$field]['name'][$index];
					$this->tempname = $_FILES[$field]['tmp_name'][$index];
					$this->size = $_FILES[$field]['size'][$index];
					$this->mimetype = $_FILES[$field]['type'][$index];
					if (php_version_at_least("4.2.0")) 
						$this->error = $_FILES[$field]['error'][$index];
				}
				$this->error = FILEUPLOAD_NO_ERROR;
			}
		} else {
			global $HTTP_POST_FILES;
			if (isset($HTTP_POST_FILES)) {
				if ($index === false) {
					$this->destname = $HTTP_POST_FILES[$field]['name'];
					$this->tempname = $HTTP_POST_FILES[$field]['tmp_name'];
					$this->size = $HTTP_POST_FILES[$field]['size'];
					$this->mimetype = $HTTP_POST_FILES[$field]['type'];
				} else {
					$this->destname = $HTTP_POST_FILES[$field]['name'][$index];
					$this->tempname = $HTTP_POST_FILES[$field]['tmp_name'][$index];
					$this->size = $HTTP_POST_FILES[$field]['size'][$index];
					$this->mimetype = $HTTP_POST_FILES[$field]['type'][$index];
				}
				$this->error = FILEUPLOAD_NO_ERROR;
			}
		}
	}

	function status() {

		if ($this->error == FILEUPLOAD_NOT_INITIALIZED) return $this->error;

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
					break;
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
			if (! $is_up) $ret = FILEUPLOAD_NOT_UPLOADED;
		} else {
			if (!$tmp_file = get_cfg_var('upload_tmp_dir')) {
				$tmp_file = dirname(tempnam('', ''));
			}
			$tmp_file .= '/' . basename($filename);
			/* User might have trailing slash in php.ini... */
			$is_up = (ereg_replace('/+', '/', $tmp_file) == $filename);
			if (! $is_up) $ret = FILEUPLOAD_BAD_NAME;
		}
		
		return $ret;
	}

	function completed() { 
		#echo "<p>Status: ".$this->status()."</p>";
		#echo "<p>";
		#foreach ($_FILES[$this->field] as $key=>$val) echo "$key = $val <br />";
		#echo "</p>";
		return ($this->status() == FILEUPLOAD_NO_ERROR); 
	}

	function moveFile() {
	/*
		if (php_version_at_least("4.0.3")) {
			$ret = move_uploaded_file($this->tempname, $this->destdir.PATH_DELIM.$this->destname);
		} else {
			$tmp_dir = ini_get("upload_tmp_dir");
			$fs = CreateFS();
			$ret = $fs->rename($tmp_dir.PATH_DELIM.$this->tempname, 
			              $this->destdir.PATH_DELIM.$this->destname);
			$fs->destruct();
		}
	*/
		$tmp_dir = ini_get("upload_tmp_dir");
		echo "<p>$tmp_dir</p><p>".$this->tempname."</p>";
		$fs = CreateFS();
		if (is_file($this->tempname)) $tmp_path = $this->tempname;
		else $tmp_path = $tmp_dir.PATH_DELIM.$this->tempname;
		$ret = $fs->copy($tmp_path, $this->destdir.PATH_DELIM.$this->destname);
		$fs->destruct();
		return $ret;
	}

	function errorMessage($err=false) {
		if (!$err) $err = $this->error;
		switch ($err) {
			case FILEUPLOAD_NO_ERROR:
				$ret = "File successfully uploaded.";
				break;
			case FILEUPLOAD_NO_FILE:
				$ret = "No file was uploaded.";
				break;
			case FILEUPLOAD_FILE_EMPTY:
				$ret = "File empty.";
				break;
			case FILEUPLOAD_SERVER_TOO_BIG:
				$ret = "File too big - rejected by server.";
				break;
			case FILEUPLOAD_FORM_TOO_BIG:
				$ret = "File too big - rejected by browser.";
				break;
			case FILEUPLOAD_PARTIAL_FILE:
				$ret = "File only partially uploaded.";
				break;
			case FILEUPLOAD_NOT_UPLOADED:
				$ret = "Not a valid file upload.";
				break;
			case FILEUPLOAD_NAME_TRUNCATED:
				$ret = "File name error - possible name truncation.";
				break;
			case FILEUPLOAD_BAD_NAME:
				$ret = "Not a valid file upload - filename error.";
				break;
			case FILEUPLOAD_NOT_INITIALIZED:
				$ret = "Invalid field name - upload not initialized.";
				break;
		}
		return $ret;
	}
}

?>
