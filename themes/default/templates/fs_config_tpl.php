<?php if (isset($FORM_MESSAGE)) { ?>
<p><strong style="color: red"><?php echo $FORM_MESSAGE; ?></strong></p>
<?php } ?>
<form method="post" action="<?php echo $FORM_ACTION; ?>">
<fieldset>
<legend style="font-weight: bold"><?php p_("Path Information"); ?></legend>
<h3><?php p_("Document Root"); ?></h3>
<p><?php pf_('To compute <abbr title="Uniform Resource Locator">URL</abbr>s, %s needs to know the full path to your document root on the web server.',
PACKAGE_NAME);?></p>
<div>
<label for="docroot"><?php p_("Web document root directory"); ?></label>
<input type="text" name="docroot" id="docroot" <?php if (isset($DOC_ROOT)) { echo 'value="'.$DOC_ROOT.'"'; } ?> />
<p style="text-align: center; margin: 0">
<a href="docroot_test.php" onclick="return doc_test();"><?php p_("Test Document Root"); ?></a>
</p>
</div>
<h3><?php p_("Subdomain root and main domain");?></h3>
<p>
<?php pf_("If your host does not support subdomains, or if you do not plan to use them with %s, then skip this section.", PACKAGE_NAME);?>
</p>
<p id="subdom_desc">
<?php pf_("If you plan to put blogs on subdomains, %s also needs to know your domain name and your subdomain root directory.  Normally, your host will create a directory for each subdomain you have, and the subdomain root is the directory in which those directories are stored.  For examlpe, if you have subdomains 'bob.example.com' and 'jeff.example.com', which are stored in the directories /home/whatever/www/bob and /home/whatever/www/jeff, then your subdomain root would be /home/whatever/www.", PACKAGE_NAME);?>
</p>
<div>
<label for="subdomroot"><?php p_("Subdomain root directory");?></label>
<input type="text" name="subdomroot" id="subdomroot" <?php if (isset($SUBDOM_ROOT)) { echo 'value="'.$SUBDOM_ROOT.'"'; } ?> />
</div>
<div>
<label for="domain"><?php p_("Domain name (e.g. mydomain.com)");?></label>
<input type="text" name="domain" id="domain" <?php if (isset($SUBDOM_ROOT)) { echo 'value="'.$SUBDOM_ROOT.'"'; } ?> />
</div>
</fieldset>
<h3><?php p_("Configure File Writing"); ?></h3>
<p id="filewrite_desc"><?php pf_('Before using %s, you must configure file writing support.  You can choose from "native" file writing functions or <abbr title="File Transfer Protocol">FTP</abbr> file writing.  In some cases, you must also choose appropriate file permissions.  The correct choice depends on the configuration of your web server.  As a general rule, if you are running PHP as an Apache module and have <strong>safe_mode</strong> enabled, then you must use <abbr title="File Transfer Protocol">FTP</abbr>.  If you are running PHP as CGI using suexec, then you should choose native file writing with default permissions.  You can use the option buttons below to guide your choice.', 
PACKAGE_NAME, PACKAGE_NAME); ?></p>
<fieldset>
<legend style="font-weight: bold"><?php p_("Common configurations");?></legend>
<p><?php p_("Choose one of these configuration options or manually set the file writing mode and file permissions below.");?></p>
<ul>
<li>
<input type="radio" name="hosttype" id="suexec" value="suexec" <?php if ($HOSTTYPE == 'suexec') { ?>checked="checked"<?php } ?> />
<label for="suexec"><?php p_("PHP running as CGI under your user account.");?></label>
</li>
<li>
<input type="radio" name="hosttype" id="mod_nosm" value="mod_nosm" <?php if ($HOSTTYPE == 'mod_nosm') { ?>checked="checked"<?php } ?> />
<label for="mod_nosm"><?php p_("PHP running as Apache module, safe-mode <strong>disabled</strong>, create files as your FTP account.");?></label>
</li>
<li>
<input type="radio" name="hosttype" id="mod_nosmroot" value="mod_nosmroot" <?php if ($HOSTTYPE == 'mod_nosmroot') { ?>checked="checked"<?php } ?> />
<label for="mod_nosmroot"><?php p_("PHP running as Apache module, safe-mode <strong>disabled</strong>, handle file permission manually.");?></label>
</li>
<li>
<input type="radio" name="hosttype" id="mod_sm" value="mod_sm" <?php if ($HOSTTYPE == 'mod_sm') { ?>checked="checked"<?php } ?> />
<label for="mod_sm"><?php p_("PHP running as Apache module, safe-mode <strong>enabled</strong>");?></label>
</li>
</ul>
</fieldset>
<fieldset>
<legend style="font-weight: bold"><?php p_("File writing mode");?></legend>
<div>
<label for="native"><?php p_('Use native functions for file writing'); ?></label>
<input type="radio" name="use_ftp" id="native" <?php if (! isset($USE_FTP)) { ?>checked="checked"<?php } ?> value="nativefs" onfocus="show_ftp(false)" />
</div>
<div>
<label for="ftpfs"><?php p_('Use FTP file writing'); ?></label>
<input type="radio" name="use_ftp" id="ftpfs" <?php if (isset($USE_FTP)) { ?>checked="checked"<?php } ?> value="ftpfs" onfocus="show_ftp(true)" />
</div>
<div id="ftpoptions">
<p><?php p_('Fill in your FTP account information below.  Note: the root directory will be auto-detected if you leave it blank.  The prefix is normally not required.'); ?></p>
<div>
<label for="ftp_user" title="<?php p_('An FTP user account that can write to the web directories.'); ?>"><?php p_('Username'); ?></label>
<input type="text"  title="<?php p_('An FTP user account that can write to the web directories.'); ?>" name="ftp_user" id="ftp_user" <?php if (isset($USER)) { echo ' value="'.$USER.'"'; } ?> />
</div>
<div>
<label for="ftp_pwd"><?php p_('Password'); ?></label>
<input type="password" name="ftp_pwd" id="ftp_pwd" <?php if (isset($PASS)) { echo 'value="'.$PASS.'"'; } ?> />
</div>
<div>
<label for="ftp_conf"><?php p_('Confirm password'); ?></label>
<input type="password" name="ftp_conf" id="ftp_conf" <?php if (isset($CONF)) { echo 'value="'.$CONF.'"'; } ?> />
</div>
<div>
<label for="ftp_host" title="<?php p_('The hostname and/or domain of the FTP server.  If the FTP and HTTP servers are on the same machine, this can be localhost.'); ?>"><?php p_('Server name'); ?></label>
<input type="text"  title="<?php p_('The hostname and/or domain of the FTP server.  If this FTP and HTTP servers are on the same machine, this can be localhost.'); ?>" name="ftp_host" id="ftp_host" <?php if (isset($HOST)) { echo 'value="'.$HOST.'"'; } ?> />
</div>
<div>
<label for="ftp_root" title="<?php p_('The full local path to the FTP root directory'); ?>"><?php p_('Root directory'); ?></label>
<input type="text"  title="<?php p_('The full local path to the FTP root directory'); ?>" name="ftp_root" id="ftp_root" <?php if (isset($ROOT)) { echo 'value="'.$ROOT.'"'; } ?> />
<p style="text-align: center; margin: 0">
<a href="ftproot_test.php" onclick="return ftp_test();"><?php p_('Test FTP Root'); ?></a>
</p>
</div>
<div>
<label for="ftp_prefix" title="<?php p_('String prepended to all FTP paths.  Useful for references to ~username.'); ?>"><?php p_('Root directory prefix'); ?></label>
<input type="text"  title="<?php p_('String prepended to all FTP paths.  Useful for references to ~username.'); ?>" name="ftp_prefix" id="ftp_prefix" <?php if (isset($PREF)) { echo 'value="'.$PREF.'"'; } ?> />
</div>
</div>
</fieldset>
<fieldset>
<legend style="font-weight: bold"><?php p_("File Permissions");?></legend>
<?php p_("File permissions for creating files and directories.  Permissions use the standard UNIX octal values.  These do not apply to Windows hosts.");?>
<ul>
<li>
<label for="permdir"><?php p_("Directories:");?></label>
<select name="permdir" id="permdir">
<option value="0777"<?php if ($PERMDIR == '0777') { ?> checked="checked"<?php } ?>>
<?php p_("World-writable (777)");?></option>
<option value="0775"<?php if ($PERMDIR == '0775') { ?> checked="checked"<?php } ?>>
<?php p_("Owner and group writable (775)");?></option>
<option value="0755"<?php if ($PERMDIR == '0755') { ?> checked="checked"<?php } ?>>
<?php p_("Owner writable (755)");?></option>
<option value="0000"<?php if ($PERMDIR == '0000') { ?> checked="checked"<?php } ?>>
<?php p_("Use server defaults");?></option>
</select>
</li>
<li>
<label for="permscript"><?php p_("PHP scripts:");?></label>
<select name="permscript" id="permscript">
<option value="0777"<?php if ($PERMSCRIPT == '0777') { ?> checked="checked"<?php } ?>>
<?php p_("World-writable (777)");?></option>
<option value="0775"<?php if ($PERMSCRIPT == '0775') { ?> checked="checked"<?php } ?>>
<?php p_("Owner and group writable (775)");?></option>
<option value="0755"<?php if ($PERMSCRIPT == '0755') { ?> checked="checked"<?php } ?>
><?php p_("Owner writable (755)");?></option>
<option value="0000"<?php if ($PERMSCRIPT == '0000') { ?> checked="checked"<?php } ?>>
<?php p_("Use server defaults");?></option>
</select>
</li>
<li>
<label for="permfile"><?php p_("Other files:");?></label>
<select name="permfile" id="permfile">
<option value="0666"<?php if ($PERMFILE == '0666') { ?> checked="checked"<?php } ?>>
<?php p_("World-writable (666)");?></option>
<option value="0664"<?php if ($PERMFILE == '0664') { ?> checked="checked"<?php } ?>>
<?php p_("Owner and group writable (664)");?></option>
<option value="0644"<?php if ($PERMFILE == '0644') { ?> checked="checked"<?php } ?>>
<?php p_("Owner writable (644)");?></option>
<option value="0000"<?php if ($PERMFILE == '0000') { ?> checked="checked"<?php } ?>>
<?php p_("Use server defaults");?></option>
</select>
</li>
</ul>
</fieldset>
<div>
<span class="basic_form_submit"><input type="submit" value="<?php p_('Submit'); ?>" /></span>
<span class="basic_form_clear"><input type="reset" value="<?php p_('Clear'); ?>" /></span>
</div>
</form>
