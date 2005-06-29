<?php 
$EXCLUDE_FS = true;
require_once("blogconfig.php");
require_once("utils.php");
$curr_dir = getcwd();
if (POST("docroot")) {
	$doc_root = POST("docroot");
} else {
	#$doc_root = find_document_root($curr_dir);
	$doc_root = calculate_document_root();
	define("DOCUMENT_ROOT", $doc_root);
}
$target_url = localpath_to_uri($curr_dir.PATH_DELIM."Readme.html");
$documentation_path = $doc_root.PATH_DELIM.basename($curr_dir).PATH_DELIM."Readme.html";
$documentation_exists = file_exists($documentation_path);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Test Document Root</title>
<script type="text/javascript">
<!--
function test_window() {
	var path = '<?php echo $target_url; ?>';
	if (path != '') {
		window.open(path);
	}
	return false;
}
-->
</script>
</head>
<body>
<h3>Document Root Test</h3>
<p>This page tests values for your document root, which is the full path on 
the web server where your web pages are stored.  To test a new value, type it
in the input box and press the "Submit" button.  The page will check that the<?php echo PACKAGE_NAME; ?> documentation exists based on the supplied
document root.</p>
<p>To determine success, check the results section below.  If the file exists
and the documentation appears in a new window when you click the test link, 
then the document root is correct.  If the file does not exist or the popup 
window shows a page not found error, then you will have to try another value.
</p>
<p><strong>Note:</strong> This page assumes that <?php echo PACKAGE_NAME; ?>
is installed in your document root.</p>
<p><strong>Note:</strong> JavaScript is required for this page to work.</p>
<p>Current Directory: <?php echo $curr_dir; ?></p>
<div>
<form method="post" action="<?php echo current_file(); ?>">
<label for="docroot">Document Root</label>
<input type="text" id="docroot" name="docroot" value="<?php echo $doc_root; ?>" />
<input type="submit" value="Test" />
</div>
<div>
<h3>Results</h3>
<p>
Document root: <?php echo $doc_root; ?><br />
Path to documentation: <?php echo $documentation_path; ?><br />
This file 
<span style="color: <?php echo $documentation_exists ? "green" : "red"; ?>">
<?php echo $documentation_exists ? "exists" : "does not exist"; ?></span>.</p>
<label for="testlink">Test link:</label> <a href="<?php echo $target_url; ?>" onclick="return test_window();"><?php echo $target_url; ?></a>
</div>
</form>
</body>
</html>
