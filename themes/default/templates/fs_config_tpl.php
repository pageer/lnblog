<?php if (isset($FORM_MESSAGE)) { ?>
<p><strong style="color: red"><?php echo $FORM_MESSAGE; ?></strong></p>
<?php } ?>
<form method="post" action="<?php echo $FORM_ACTION; ?>">
<?php $this->outputCsrfField() ?>
<fieldset>
<legend style="font-weight: bold"><?php p_("Path Information"); ?></legend>
<h3><?php p_("Document Root"); ?></h3>
<p><?php pf_('To compute <abbr title="Uniform Resource Locator">URL</abbr>s, %s needs to know the full path and URL to itself and the directory where user information (profiles, configuration, etc.) will be stored.  When in doubt, leave the defaults.',
PACKAGE_NAME);?></p>
<div>
<label for="installroot"><?php pf_("%s root directory", PACKAGE_NAME); ?></label>
<input type="text" name="installroot" id="installroot" <?php if (isset($INSTALL_ROOT)) { echo 'value="'.$INSTALL_ROOT.'"'; } ?> />
</div>
<div>
<label for="installrooturl"><?php pf_("%s URL", PACKAGE_NAME); ?></label>
<input type="text" name="installrooturl" id="installrooturl" <?php if (isset($INSTALL_ROOT_URL)) { echo 'value="'.$INSTALL_ROOT_URL.'"'; } ?> />
</div>
<div>
<label for="userdata"><?php p_("Userdata directory"); ?></label>
<input type="text" name="userdata" id="userdata" <?php if (isset($USERDATA)) { echo 'value="'.$USERDATA.'"'; } ?> />
</div>
<div>
<label for="userdata"><?php p_("Userdata URL"); ?></label>
<input type="text" name="userdataurl" id="userdataurl" <?php if (isset($USERDATA_URL)) { echo 'value="'.$USERDATA_URL.'"'; } ?> />
</div>
</fieldset>
<div>
<span class="basic_form_submit"><input type="submit" value="<?php p_('Submit'); ?>" /></span>
<span class="basic_form_clear"><input type="reset" value="<?php p_('Clear'); ?>" /></span>
</div>
</form>
