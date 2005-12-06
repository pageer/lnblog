<h2><?php p_("File System Configuration"); ?></h2>
<?php if (isset($FORM_MESSAGE)) { ?>
<p><strong style="color: red"><?php echo $FORM_MESSAGE; ?></strong></p>
<?php } ?>
<form method="post" action="<?php echo $FORM_ACTION; ?>">
<h3><?php p_("Set Document Root"); ?></h3>
<p><?php pf_('To compute <abbr title="Uniform Resource Locator">URL</abbr>s, 
%s needs to know the full path to your document root on the web server.  
The auto-detected value is usually right, but if not, you can change it here.',
PACKAGE_NAME);?></p>
<div>
<label for="<?php echo $DOC_ROOT_ID; ?>"><?php p_("Web document root directory"); ?></label>
<input type="text" name="<?php echo $DOC_ROOT_ID; ?>" id="<?php echo $DOC_ROOT_ID; ?>" <?php if (isset($DOC_ROOT)) { echo 'value="'.$DOC_ROOT.'"'; } ?> />
<p style="text-align: center; margin: 0">
<a href="docroot_test.php" onclick="return doc_test();"><?php p_("Test Document Root"); ?></a>
</p>
</div>
<h3><?php p_("Configure File Writing"); ?></h3>
<p><?php pf_('Before using %s, you must configure file writing
support.  You can choose from "native" file writing functions or 
<abbr title="File Transfer Protocol">FTP</abbr> file writing.  If you 
want to use %s with PHP\'s <strong>safe_mode</strong> enabled, then you
must use <abbr title="File Transfer Protocol">FTP</abbr>.', 
PACKAGE_NAME, PACKAGE_NAME); ?></p>
<div>
<label for="native"><?php p_('Use native functions for file writing'); ?></label>
<input type="radio" name="<?php echo $USE_FTP_ID; ?>" id="native" <?php if (! isset($USE_FTP)) { ?>checked="checked"<?php } ?> value="nativefs" onfocus="show_ftp(false)" />
</div>
<div>
<label for="ftpfs"><?php p_('Use FTP file writing'); ?></label>
<input type="radio" name="<?php echo $USE_FTP_ID; ?>" id="ftpfs" <?php if (isset($USE_FTP)) { ?>checked="checked"<?php } ?> value="ftpfs" onfocus="show_ftp(true)" />
</div>
<div id="ftpoptions">
<script type="text/javascript">
show_ftp(document.getElementById('ftpfs').nodeValue == 'ftpfs');
</script>
<p><?php p_('Note: the root directory will be auto-detected if you leave it
blank.  The prefix is normally not required.'); ?></p>
<div>
<label for="<?php echo $USER_ID; ?>" title="<?php p_('An FTP user account that can write to the web directories.'); ?>"><?php p_('Username'); ?></label>
<input type="text"  title="<?php p_('An FTP user account that can write to the web directories.'); ?>" name="<?php echo $USER_ID; ?>" id="<?php echo $USER_ID; ?>"<?php if (isset($USER)) { echo ' value="'.$USER.'"'; } ?> />
</div>
<div>
<label for="<?php echo $PASS_ID; ?>"><?php p_('Password'); ?></label>
<input type="password" name="<?php echo $PASS_ID; ?>" id="<?php echo $PASS_ID; ?>" <?php if (isset($PASS)) { echo 'value="'.$PASS.'"'; } ?> />
</div>
<div>
<label for="<?php echo $CONF_ID; ?>"><?php p_('Confirm password'); ?></label>
<input type="password" name="<?php echo $CONF_ID; ?>" id="<?php echo $CONF_ID; ?>" <?php if (isset($CONF)) { echo 'value="'.$CONF.'"'; } ?> />
</div>
<div>
<label for="<?php echo $HOST_ID; ?>" title="<?php p_('The hostname and/or domain of the FTP server.  If the FTP and HTTP servers are on the same machine, this can be localhost.'); ?>"><?php p_('Server name'); ?></label>
<input type="text"  title="<?php p_('The hostname and/or domain of the FTP server.  If this FTP and HTTP servers are on the same machine, this can be localhost.'); ?>" name="<?php echo $HOST_ID; ?>" id="<?php echo $HOST_ID; ?>" <?php if (isset($HOST)) { echo 'value="'.$HOST.'"'; } ?> />
</div>
<div>
<label for="<?php echo $ROOT_ID; ?>" title="<?php p_('The full local path to the FTP root directory'); ?>"><?php p_('Root directory'); ?></label>
<input type="text"  title="<?php p_('The full local path to the FTP root directory'); ?>" name="<?php echo $ROOT_ID; ?>" id="<?php echo $ROOT_ID; ?>" <?php if (isset($ROOT)) { echo 'value="'.$ROOT.'"'; } ?> />
<p style="text-align: center; margin: 0">
<a href="ftproot_test.php" onclick="return ftp_test();"><?php p_('Test FTP Root'); ?></a>
</p>
</div>
<div>
<label for="<?php echo $PREF_ID; ?>" title="<?php p_('String prepended to all FTP paths.  Useful for references to ~username.'); ?>"><?php p_('Root directory prefix'); ?></label>
<input type="text"  title="<?php p_('String prepended to all FTP paths.  Useful for references to ~username.'); ?>" name="<?php echo $PREF_ID; ?>" id="<?php echo $PREF_ID; ?>" <?php if (isset($PREF)) { echo 'value="'.$PREF.'"'; } ?> />
</div>
</div>
<div>
<span class="basic_form_submit"><input type="submit" value="<?php p_('Submit'); ?>" /></span>
<span class="basic_form_clear"><input type="reset" value="<?php p_('Clear'); ?>" /></span>
</div>
</form>
