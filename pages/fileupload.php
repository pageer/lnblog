<?php
require_once("upload.php");
require_once("blog.php");
require_once("template.php");

$blog = new Blog;

$file_name = "filename";

$tpl = new PHPTemplate(UPLOAD_TEMPLATE);
$tpl->set("TARGET", current_file() );
$tpl->set("MAX_SIZE", 2000000); #ini_get("upload_max_filesize"));
$tpl->set("FILE", $file_name);

$f = new FileUpload($file_name);
if ($f->status() == FILEUPLOAD_NOT_INITIALIZED) {
	$msg = "Select a file to upload to the current directory.  The file size".
	       " limit is ".ini_get("upload_max_filesize").".";
	$tpl->set("UPLOAD_MESSAGE", $msg);
} else {
	$tpl->set("UPLOAD_MESSAGE", $f->errorMessage() );
	if ( $f->completed() ) $f->moveFile();
}
$body = $tpl->process();

$tpl->file = BASIC_LAYOUT_TEMPLATE;
$blog->exportVars($tpl);
$tpl->set("PAGE_TITLE", "Upload file");
$tpl->set("PAGE_CONTENT", $body);

echo $tpl->process();
?>
