<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title><?php p_("Test Document Root"); ?></title>
<script type="text/javascript">
<!--
function test_window() {
	var path = '<?php echo $TARGET_URL; ?>';
	if (path != '') {
		window.open(path);
	}
	return false;
}
-->
</script>
</head>
<body>
<h3><?php p_("Document Root Test"); ?></h3>
<p><?php pf_('This page tests values for your document root, which is the full
path on the web server where your web pages are stored.  To test a new value,
type it in the input box and press the "Submit" button.  The page will check that the %s documentation exists based on the 
supplied document root.', PACKAGE_NAME); ?></p>
<p><?php p_("To determine success, check the results section below.  If the 
file exists and the documentation appears in a new window when you click the 
test link, then the document root is correct.  If the file does not exist or 
the popup window shows a page not found error, then you will have to try 
another value."); ?></p>
<p><strong><?php p_("Note:"); ?></strong> <?php p_("This page assumes that 
%s is installed in your document root.  JavaScript must be enabled.", PACKAGE_NAME); ?></p>
<p><?php pf_("Current Directory: %s", $CURR_DIR); ?></p>
<div>
<form method="post" action="<?php echo $TARGETFILE; ?>">
<label for="docroot"><?php p_("Document Root"); ?></label>
<input type="text" id="docroot" name="docroot" value="<?php echo $DOC_ROOT; ?>" />
<input type="submit" value="<?php p_("Test"); ?>" />
</div>
<div>
<h3><?php p_("Results"); ?></h3>
<p>
<?php pf_("Document root: %s", $DOC_ROOT); ?><br />
<?php pf_("Path to documentation: %s", $DOCUMENTATION_PATH); ?><br />
<?php if ($DOCUMENTATION_EXISTS) { ?>
<span style="color: green"><?php p_("This file exists.  Test passed."); ?></span>
<?php } else { ?>
<span style="color: green"><?php p_("This file does not exists.  Test failed."); ?></span>
<?php } ?>
<?php if ($DOCUMENTATION_EXISTS) { ?>
<span style="color: green"><?php p_("This file exists.  Test passed."); ?></span>
<?php } else { ?>
<span style="color: red"><?php p_("This file does not exist.  Test failed."); ?></span>
<?php } ?>
</p>
<label for="testlink"><?php p_("Test link"); ?>:</label> <a href="<?php echo $TARGET_URL; ?>" onclick="return test_window();"><?php echo $TARGET_URL; ?></a>
</div>
</form>
</body>
</html>
