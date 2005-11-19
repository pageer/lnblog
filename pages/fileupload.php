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
# Used to upload a file to the web server.
#
# This is included by the uploadfile.php wrapper script for weblogs, 
# blog entries, and articles.

session_start();

require_once("lib/creators.php");

$blog = NewBlog();
$ent = NewBlogEntry();
$tpl = NewTemplate(UPLOAD_TEMPLATE);
$page = NewPage();

if ( ($ent->isEntry() && $blog->canModifyEntry()) || 
     $blog->canModifyBlog()) {

	$file_name = "filename";
	$f = NewFileUpload($file_name);
	
	$tpl->set("TARGET", current_file() );
	$size = ini_get("upload_max_filesize");
	$size = str_replace("K", "000", $size);
	$size = str_replace("M", "000000", $size);
	$tpl->set("MAX_SIZE", $size);
	$tpl->set("FILE", $file_name);
	$tpl->set("TARGET_URL", localpath_to_uri($f->destdir) );

	if ($f->status() == FILEUPLOAD_NOT_INITIALIZED) {
		$msg = spf_("Select a file to upload to the current directory.  The file size limit is %s.",
		               ini_get("upload_max_filesize"));
		$tpl->set("UPLOAD_MESSAGE", $msg);
	} else {
		$tpl->set("UPLOAD_MESSAGE", $f->errorMessage() );
		if ( $f->completed() ) $f->moveFile();
	}
	$body = $tpl->process();

} else {
	$body = "<h3>";
	if ($ent->isEntry()) $body .= _("You do not have permission to upload files to this entry.");
	else $body .= _("You do not have permission to upload files to this weblog.");
	$body .= "</h3>";
}

$page->addStylesheet("form.css");
$page->title = _("Upload file");
$page->display($body, &$blog);

?>
