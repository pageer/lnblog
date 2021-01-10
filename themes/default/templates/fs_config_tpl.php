<?php if (isset($FORM_MESSAGE)) { ?>
<p><strong style="color: red"><?php echo $FORM_MESSAGE; ?></strong></p>
<?php } ?>
<form method="post" action="<?php echo $FORM_ACTION; ?>">
<?php $this->outputCsrfField() ?>
<style>
.inputbox input {
    width: 90%;
}
.inputbox label {
    font-weight: bold;
}
</style>
<fieldset>
<legend style="font-weight: bold"><?php p_("Path Information"); ?></legend>
<h3><?php p_("Document Root"); ?></h3>
<p>
<?php pf_('To compute <abbr title="Uniform Resource Locator">URL</abbr>s, %1$s needs to know the full path and URL to itself and the directory where user information (profiles, configuration, etc.) will be stored.  These will be stored in the %2$s file in the %1$s directory.  If you need to change the paths or URLs in the future, you should edit that file.  You can also rename the file and visit this page again to re-create it.', PACKAGE_NAME, SystemConfig::PATH_CONFIG_NAME);?>
</p>
<p>
<?php p_('The correct paths will depend on your server configuration.  When in doubt, leave the defaults.')?>
</p>
<div class="inputbox">
<label for="installroot"><?php pf_("%s root directory", PACKAGE_NAME); ?></label>
<br>
<input type="text" name="installroot" id="installroot" <?php echo isset($INSTALL_ROOT) ? 'value="'.$INSTALL_ROOT.'"' : ''?> />
</div>
<div class="inputbox">
<label for="installrooturl"><?php pf_("%s URL", PACKAGE_NAME); ?></label>
<br>
<input type="text" name="installrooturl" id="installrooturl" <?php echo isset($INSTALL_ROOT_URL) ?  'value="'.$INSTALL_ROOT_URL.'"' : '' ?> />
</div>
<div class="inputbox">
<label for="userdata"><?php p_("Userdata directory"); ?></label>
<br>
<input type="text" name="userdata" id="userdata" <?php echo isset($USERDATA) ? 'value="'.$USERDATA.'"' : '' ?> />
</div>
<div class="inputbox">
<label for="userdata"><?php p_("Userdata URL"); ?></label>
<br>
<input type="text" name="userdataurl" id="userdataurl" <?php echo isset($USERDATA_URL) ? 'value="'.$USERDATA_URL.'"' : '' ?> />
</div>
</fieldset>
<div>
<span class="basic_form_submit"><input type="submit" value="<?php p_('Submit'); ?>" /></span>
<span class="basic_form_clear"><input type="reset" value="<?php p_('Clear'); ?>" /></span>
</div>
</form>
