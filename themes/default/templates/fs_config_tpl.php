<script type="text/javascript">
<!--
function doc_test() {
	window.open("docroot_test.php");
	return false;
}

function ftp_test() {
	window.open("ftproot_test.php");
	return false;
}
-->
</script>
<h2>File System Configuration</h2>
<?php if (isset($FORM_MESSAGE)) { ?>
<p><strong style="color: red"><?php echo $FORM_MESSAGE; ?></strong></p>
<?php } ?>
<form method="post" action="<?php echo $FORM_ACTION; ?>">
<h3>Set Document Root</h3>
<p>To compute <abbr title="Uniform Resource Locator">URL</abbr>s, 
<?php echo PACKAGE_NAME; ?> needs to know the full path to your document root
on the web server.  This is the directory where your web pages are stored.  
<?php echo PACKAGE_NAME; ?> will guess a location by testing common directory
names, but you may have to set this manually.  To check verify the document
root or test a different value, click the "Test Document Root" link below.</p>
<div>
<label for="<?php echo $DOC_ROOT_ID; ?>">Web document root directory</label>
<input type="text" name="<?php echo $DOC_ROOT_ID; ?>" id="<?php echo $DOC_ROOT_ID; ?>" <?php if (isset($DOC_ROOT)) { echo 'value="'.$DOC_ROOT.'"'; } ?> />
<p style="text-align: center; margin: 0">
<a href="docroot_test.php" onclick="return doc_test();">Test Document Root</a>
</p>
</div>

<h3>Configure File Writing</h3>
<p>Before using <?php echo PACKAGE_NAME;?>, you must configure file writing
support.  You can choose from "native" file writing functions or 
<abbr title="File Transfer Protocol">FTP</abbr> file writing.</p>
<p>Under native file writing, all files and directories created by 
<?php echo PACKAGE_NAME;?> will be owned by the web server user (typically 
"nobody" or "apache") and you will have problems trying to access them 
outside <?php echo PACKAGE_NAME;?>.  With <abbr title="File Transfer Protocol">FTP</abbr>
file writing, you will own all the files and directories.  The only catch is 
that you have to be able to upload files to the web server through
<abbr title="File Transfer Protocol">FTP</abbr>.  If this is possible, 
<abbr title="File Transfer Protocol">FTP</abbr> file writing is the 
recommended choice.</p>
<p>For <abbr title="File Transfer Protocol">FTP</abbr> file writing, you will
need to supply the information to log in to the 
<abbr title="File Transfer Protocol">FTP</abbr> server.  In addition to the 
username and password, you will have to give a hostname and the 
<abbr title="File Transfer Protocol">FTP</abbr> root directory.  The host 
name is the name of the server, such as <code>ftp.mysite.com</code> and can
be <code>localhost</code> if the 
<abbr title="File Transfer Protocol">FTP</abbr> server is running on the same
computer as the web server.  The 
<abbr title="File Transfer Protocol">FTP</abbr> root directory is the full
path on the server to where your 
<abbr title="File Transfer Protocol">FTP</abbr> access starts.  To have
<?php echo PACKAGE_NAME; ?> try to auto-detect this, click the 
"Test FTP Root" link and enter your username, password, and hostname in the
form.  You can copy and paste the results back into this form.</p>
<div>
<label for="native">Use native functions for file writing</label>
<input type="radio" name="<?php echo $USE_FTP_ID; ?>" id="native" <?php if (! isset($USE_FTP)) { ?>checked="checked"<?php } ?> value="nativefs" />
</div>
<div>
<label for="ftpfs">Use FTP file writing</label>
<input type="radio" name="<?php echo $USE_FTP_ID; ?>" id="ftpfs" <?php if (isset($USE_FTP)) { ?>checked="checked"<?php } ?> value="ftpfs" />
</div>
<div>
<label for="<?php echo $USER_ID; ?>" title="An FTP user account that can write to the web directories.">Username</label>
<input type="text"  title="An FTP user account that can write to the web directories." name="<?php echo $USER_ID; ?>" id="<?php echo $USER_ID; ?>"<?php if (isset($USER)) { echo ' value="'.$USER.'"'; } ?> />
</div>
<div>
<label for="<?php echo $PASS_ID; ?>">Password</label>
<input type="password" name="<?php echo $PASS_ID; ?>" id="<?php echo $PASS_ID; ?>" <?php if (isset($PASS)) { echo 'value="'.$PASS.'"'; } ?> />
</div>
<div>
<label for="<?php echo $CONF_ID; ?>">Confirm password</label>
<input type="password" name="<?php echo $CONF_ID; ?>" id="<?php echo $CONF_ID; ?>" <?php if (isset($CONF)) { echo 'value="'.$CONF.'"'; } ?> />
</div>
<div>
<label for="<?php echo $HOST_ID; ?>" title="The hostname and/or domain of the FTP server.  If the FTP and HTTP servers are on the same machine, this can be localhost.">Server hostname</label>
<input type="text"  title="The hostname and/or domain of the FTP server.  If this FTP and HTTP servers are on the same machine, this can be localhost." name="<?php echo $HOST_ID; ?>" id="<?php echo $HOST_ID; ?>" <?php if (isset($HOST)) { echo 'value="'.$HOST.'"'; } ?> />
</div>
<div>
<label for="<?php echo $ROOT_ID; ?>" title="The full local path to the FTP root directory">Root directory</label>
<input type="text"  title="The full local path to the FTP root directory" name="<?php echo $ROOT_ID; ?>" id="<?php echo $ROOT_ID; ?>" <?php if (isset($ROOT)) { echo 'value="'.$ROOT.'"'; } ?> />
<p style="text-align: center; margin: 0">
<a href="ftproot_test.php" onclick="return ftp_test();">Test FTP Root</a>
</p>
</div>
<div>
<label for="<?php echo $PREF_ID; ?>" title="String prepended to all FTP paths.  Useful for references to ~username.">Root directory prefix</label>
<input type="text"  title="String prepended to all FTP paths.  Useful for references to ~username." name="<?php echo $PREF_ID; ?>" id="<?php echo $PREF_ID; ?>" <?php if (isset($PREF)) { echo 'value="'.$PREF.'"'; } ?> />
</div>
<div>
<span class="basic_form_submit"><input type="submit" value="Submit" /></span>
<span class="basic_form_clear"><input type="reset" value="Clear" /></span>
</div>
</form>
