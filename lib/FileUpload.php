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

# Class: FileUpload
# Handles file uploads via HTTP POSTs.
# Handling the upload is a three-step process.  First, you need to create an
# instance of the class, passing it the upload array and optional
# destination directory and upload array index.  After that, you check if the
# upload completed successfully, and based on that check, either move the file
# to the permanent location or emit an error message.
#
# Events:
# OnInit       - Fired when the object is created.
# InitComplete - Fired after object is initialized.
# OnMove       - Fired before moving the uploaded file to its destination.
# MoveComplete - Fired after the file is successfully moved.

class FileUpload extends LnBlogObject
{

    public $field = '';
    public $destdir = '';
    public $destname = '';
    public $tempname = '';
    public $size = 0;
    public $mimetype = '';
    public $error = FILEUPLOAD_NOT_INITIALIZED;

    private $fs;

    public static function initUploads($file, $dir, $fs = null) {
        $ret = array();
        if (is_array($file['tmp_name'])) {
            $i = 0;
            while (isset($file['tmp_name'][$i])) {
                $ret[] = new FileUpload($file, $dir, $i++, $fs);
            }
        } else {
            $ret[] = new FileUpload($file, $dir, false, $fs);
        }
        return $ret;
    }

    public function __construct($file, $dir = false, $index = false, $fs = null) {
        $this->fs = $fs ?: NewFS();
        $this->raiseEvent("OnInit");
        $this->destdir = $dir ? $dir : $this->fs->getcwd();
        $this->field = $file;

        if (! empty($file)) {
            $this->error = FILEUPLOAD_NO_ERROR;
            if ($index !== false && is_array($file['tmp_name']) ) {
                $this->destname = $file['name'][$index];
                $this->tempname = $file['tmp_name'][$index];
                $this->size = $file['size'][$index];
                $this->mimetype = $file['type'][$index];
                if (isset($file['error'])) {
                    $this->error = $file['error'][$index];
                }
            } else {
                $this->destname = $file['name'];
                $this->tempname = $file['tmp_name'];
                $this->size = $file['size'];
                $this->mimetype = $file['type'];
                if (isset($file['error'])) {
                    $this->error = $file['error'];
                }
            }
        }
        $this->raiseEvent("InitComplete");
    }

    # Method: status
    # Get a status code for the file upload.
    #
    # Returns:
    # An integer representing the upload status.

    function status() {
        $ret = FILEUPLOAD_NO_ERROR;

        if ($this->error == FILEUPLOAD_NOT_INITIALIZED) {
            return $this->error;
        }

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

        if ($ret == FILEUPLOAD_NO_ERROR && $this->size <= 0) {
            $ret = FILEUPLOAD_FILE_EMPTY;
        }

        if ($this->fs->is_file($this->tempname)) {
            $tmp_path = $this->tempname;
        } else {
            $tmp_path = Path::mk(ini_get("upload_tmp_dir"), $this->tempname);
        }

        if ($ret === FILEUPLOAD_NO_ERROR) {
            if ( ! $this->tempname || (! $this->fs->is_uploaded_file($tmp_path) && ! $this->fs->is_file($this->tempname)) ) {
                $ret = FILEUPLOAD_NO_FILE;
            }
        }

        return $ret;
    }

    # Method: completed
    # Determines the status of the upload.
    #
    # Returns:
    # True if the file uploaded without error, false otherwise.

    function completed() {
        return $this->status() == FILEUPLOAD_NO_ERROR;
    }

    # Method: moveFile
    # Moves the file from the upload directory to the permanent location.
    #
    # Retruns:
    # True on success, false otherwise.

    function moveFile() {
        $this->raiseEvent("OnMove");
        $tmp_dir = ini_get("upload_tmp_dir");
        if ($this->fs->is_file($this->tempname)) {
            $tmp_path = $this->tempname;
        } else {
            $tmp_path = Path::mk($tmp_dir, $this->tempname);
        }

        $ret = $this->fs->copy($tmp_path, Path::mk($this->destdir, $this->destname));
        if ($ret) {
            $this->raiseEvent("MoveComplete");
        }
        return $ret;
    }

    # Method: errorMessage
    # Gets a message associated with the <status> of the upload.
    #
    # Parameters:
    # err - *Optional* status code for which to get the message.  The default is
    #       to use the error property of the object.
    #
    # Returns:
    # A string containing the appropriate message.

    function errorMessage($err=false) {
        $ret = '';
        if (!$err) {
            $err = $this->status();
        }
        switch ($err) {
            case FILEUPLOAD_NO_ERROR:
                $resolver = new UrlResolver(SystemConfig::instance(), $this->fs);
                $ret = spf_(
                    "File '<a href=\"%s\">%s</a>' successfully uploaded.",
                    $resolver->absoluteLocalpathToUri(Path::mk($this->destdir, $this->destname)),
                    $this->destname
                );
                break;
            case FILEUPLOAD_NO_FILE:
                $ret = _("No file was uploaded.");
                break;
            case FILEUPLOAD_FILE_EMPTY:
                $ret = _("File empty.");
                break;
            case FILEUPLOAD_SERVER_TOO_BIG:
                $ret = _("File too big - rejected by server.");
                break;
            case FILEUPLOAD_FORM_TOO_BIG:
                $ret = _("File too big - rejected by browser.");
                break;
            case FILEUPLOAD_PARTIAL_FILE:
                $ret = _("File only partially uploaded.");
                break;
            case FILEUPLOAD_NOT_UPLOADED:
                $ret = _("Not a valid file upload.");
                break;
            case FILEUPLOAD_NAME_TRUNCATED:
                $ret = _("File name error - possible name truncation.");
                break;
            case FILEUPLOAD_BAD_NAME:
                $ret = _("Not a valid file upload - filename error.");
                break;
            case FILEUPLOAD_NOT_INITIALIZED:
                $ret = _("Invalid field name - upload not initialized.");
                break;
        }
        return $ret;
    }
}
