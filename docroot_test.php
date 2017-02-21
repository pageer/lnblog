<?php 

# File: docroot_test.php
# This is a one-off page to test if a given value is valid as the
# DOCUMENT_ROOT setting for LnBlog.
#
# The page will attempt to guess the document root, but will also allow the
# user to enter one manually.  It will then use this path to build a local
# path and URI to the LnBlog ReadMe file.  The idea is that if the path
# to the ReadMe exists and the URI to access it works, then the 
# DOCUMENT_ROOT is correct.

$EXCLUDE_FS = true;
require_once __DIR__."/blogconfig.php";
require_once __DIR__."/lib/constants.php";
require_once __DIR__."/lib/creators.php";
require_once __DIR__."/lib/utils.php";
$tpl = NewTemplate(DOCROOT_TEST_TEMPLATE);
$curr_dir = getcwd();
$tpl->set("CURR_DIR", $curr_dir);
$tpl->set("TARGETFILE", current_file());
if (POST("docroot")) {
	$doc_root = POST("docroot");
} else {
	$doc_root = calculate_document_root();
	define("DOCUMENT_ROOT", $doc_root);
}
$tpl->set("DOC_ROOT", $doc_root);
$target_url = localpath_to_uri($curr_dir.PATH_DELIM."ReadMe.txt");
$tpl->set("TARGET_URL", $target_url);
$documentation_path = $doc_root.PATH_DELIM.basename($curr_dir).PATH_DELIM."ReadMe.txt";
$tpl->set("DOCUMENTATION_PATH", $documentation_path);
$documentation_exists = file_exists($documentation_path);
$tpl->set("DOCUMENTATION_EXISTS", $documentation_exists);
echo $tpl->process();
?>
