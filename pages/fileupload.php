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

# File: fileupload.php
# Used to upload a file to the web server.  This is used to upload files
# to entries, profiles, and the blog itself.

session_start();

require_once("config.php");
require_once("lib/creators.php");

$num_fields = 5;
$target_under_blog = "";

$blog = NewBlog();
$ent = NewBlogEntry();
$usr = NewUser();
$tpl = NewTemplate(UPLOAD_TEMPLATE);
$tpl->set("NUM_UPLOAD_FIELDS", $num_fields);

$target = false;
if ( isset($_GET["profile"]) &&  
     ($_GET["profile"] == $usr->username() || $usr->isAdministrator()) ) {
	$target = Path::mk(USER_DATA_PATH, $usr->username());
} elseif ($ent->isEntry() && $SYSTEM->canModify($ent, $usr)) {
	$target = $ent->localpath();
} elseif ($SYSTEM->canModify($blog, $usr)) {
	$target = $blog->home_path;
	if ($target_under_blog) $target = mkpath($target, $target_under_blog);
}

# Check that the user is logged in.
if (! $usr->checkLogin()) $target = false;

if ($target) {

	$file_name = "upload";
	
	$query_string = isset($_GET["blog"])?"?blog=".$_GET["blog"]:'';
	$query_string .= isset($_GET["profile"]) ?
	                 ($query_string?"&amp;":"?")."profile=".$_GET["profile"] :
	                 "";
	
	$tpl->set("TARGET", current_file().$query_string);
	$size = ini_get("upload_max_filesize");
	$size = str_replace("K", "000", $size);
	$size = str_replace("M", "000000", $size);
	$tpl->set("MAX_SIZE", $size);
	$tpl->set("FILE", $file_name);
	$tpl->set("TARGET_URL", localpath_to_uri($target) );

	if (! empty($_FILES)) {
		$files = FileUpload::initUploads($_FILES[$file_name], $target);
	
		$msg = '';
		foreach ($files as $f) {
			if ($f->status() != FILEUPLOAD_NOT_INITIALIZED && $f->size > 0) {
				$err = $f->errorMessage();
				if ($msg) $msg .= "<br />";
				$msg .= $err;
				if ( $f->completed() ) $f->moveFile();
			}
		}
		if (! $msg) {
			$msg .= spf_("Select files to upload to the above location.  The file size limit is %s.",
						 ini_get("upload_max_filesize"));
		}
		
		$tpl->set("UPLOAD_MESSAGE", $msg);
	}
	
	$body = $tpl->process();

} else {
	$body = "<h3>";
	if ($ent->isEntry()) $body .= _("You do not have permission to upload files to this entry.");
	else $body .= _("You do not have permission to upload files to this weblog.");
	$body .= "</h3>";
}

Page::instance()->addStylesheet("form.css");
Page::instance()->title = _("Upload file");
Page::instance()->addScript('upload.js');
Page::instance()->display($body, $blog);
