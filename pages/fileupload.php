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
	$tpl->set("MAX_SIZE", 2000000); #ini_get("upload_max_filesize"));
	$tpl->set("FILE", $file_name);
	$tpl->set("TARGET_URL", localpath_to_uri($f->destdir) );

	if ($f->status() == FILEUPLOAD_NOT_INITIALIZED) {
		$msg = "Select a file to upload to the current directory.  The file size".
		       " limit is ".ini_get("upload_max_filesize").".";
		$tpl->set("UPLOAD_MESSAGE", $msg);
	} else {
		$tpl->set("UPLOAD_MESSAGE", $f->errorMessage() );
		if ( $f->completed() ) $f->moveFile();
	}
	$body = $tpl->process();

} else {
	$body = "<h3>You do not have permission to upload files to this ".
	         ($ent->isEntry()?"entry":"weblog").".</h3>";
}

$page->addStylesheet("form.css");
$page->title = "Upload file";
$page->display($body, &$blog);

?>
