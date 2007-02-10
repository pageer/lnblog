<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title><?php p_('Test FTP Root'); ?></title>
</head>
<body>
<h3><?php p_('FTP Root Test'); ?></h3>
<p><?php p_('This page will attempt to detect your <abbr title="File Transfer Protocol">FTP</abbr> root.  This is the root directory for <abbr title="File Transfer Protocol">FTP</abbr> use and is not necessarily the same as the real system root.  To run the test, enter your FTP username, password, and host name and click the "Test" button to detect the <abbr title="File Transfer Protocol">FTP</abbr> root.  You can also test other <abbr title="File Transfer Protocol">FTP</abbr> root values by filling in the "FTP Root" box.'); ?></p>
<p><?php p_('To determine success, check the results section below.  If the last line indicates that the test file was found, then you can copy the "FTP root" value into the appropriate configuration page.  If it indicates that the file was <em>not</em> found, then you will have to try another value.'); ?></p>
<p><?php p_('Current Directory'); ?>: <?php echo $CURR_DIR; ?></p>
<?php if ($ERROR_MESSAGE) { ?>
<h4><?php echo $ERROR_MESSAGE; ?></h4>
<?php } ?>
<form method="post" action="<?php echo $TARGETPAGE; ?>">
<div>
<label for="uid"><?php p_('FTP Username'); ?></label>
<input type="text" id="uid" name="uid" value="<?php echo $USER; ?>" />
</div>
<div>
<label for="pwd"><?php p_('FTP Password'); ?></label>
<input type="password" id="pwd" name="pwd" value="<?php echo $PASS; ?>" />
</div>
<div>
<label for="host"><?php p_('FTP host'); ?></label>
<input type="text" id="host" name="host" value="<?php echo $HOSTNAME; ?>" />
</div>
<div>
<label for="ftproot"><?php p_('FTP Root'); ?></label>
<input type="text" id="ftproot" name="ftproot" value="<?php echo $FTP_ROOT; ?>" />
<span><?php p_('(Optional, leave blank to auto-detect.)'); ?></span>
</div>
<div>
<input type="submit" value="<?php p_('Test'); ?>" />
</form>
<div>
<h3><?php p_('Results'); ?></h3>
<p>
<?php p_('FTP root'); ?>: <?php echo $FTP_ROOT; ?><br />
<?php p_('Test file path'); ?>: <?php echo $TEST_FILE; ?><br />
<?php p_('FTP path'); ?>: <?php echo $FTP_PATH; ?><br />
<?php if ($TEST_STATUS) { ?>
<span style="color: green"><?php p_("The test file was found.  Test passed."); ?></span>
<?php } else { ?>
<span style="color: red"><?php p_("The test file was not found.  Test failed."); ?></span>
<span style="color: <?php echo ($TEST_STATUS ? "green" : "red"); ?>">
<?php } ?>
</div>
</body>
</html>
