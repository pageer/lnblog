<h2><?php echo $FORM_TITLE; ?></h2>
<?php if (isset($FORM_MESSAGE)) { ?>
<p><?php echo $FORM_MESSAGE; ?></p>
<?php } ?>
<script type="text/javascript">
<!--
toggle_active() {
	var i = 0;
	for(i = 0; i < document.forms[0].elements.length; i++) {
		document.forms[0].elements[i].disabled = !document.forms[0].elements[i].disabled;
	}
}
-->
</script>
<form method="post" action="<?php echo $FORM_ACTION; ?>">
<div>
<label for="native">Use native functions for file writing</label>
<input type="radio" name="<?php echo $USE_FTP_ID; ?>" id="native" <?php if (! isset($USE_FTP)) { ?>checked="checked"<?php } ?> />
</div>
<div>
<label for="ftpfs">Use FTP file writing</label>
<input type="radio" name="<?php echo $USE_FTP_ID; ?>" id="ftpfs" <?php if (isset($USE_FTP)) { ?>checked="checked"<?php } ?> value="ftpfs" onclick="deactivate();" />
</div>
<div>
<label for="<?php echo $USER_ID; ?>">Username</label>
<input type="text" name="<?php echo $USER_ID; ?>" id="<?php echo $USER_ID; ?>"<?php if (isset($USER)) { ?> value="<?php echo $USER;?>"<?php } ?> />
</div>
<div>
<label for="<?php echo $PASS_ID; ?>">Password</label>
<input type="password" name="<?php echo $PASS_ID; ?>" id="<?php echo $PASS_ID; ?>"<?php if (isset($PASS)) { ?> value="<?php echo $PASS;?>"<?php } ?> />
</div>
<div>
<label for="<?php echo $CONF_ID; ?>">Confirm password</label>
<input type="password" name="<?php echo $CONF_ID; ?>" id="<?php echo $CONF_ID; ?>"<?php if (isset($CONF)) { ?> value="<?php echo $CONF;?>"<?php } ?> />
</div>
<div>
<label for="<?php echo $HOST_ID; ?>">Server hostname</label>
<input type="text" name="<?php echo $HOST_ID; ?>" id="<?php echo $HOST_ID; ?>"<?php if (isset($HOST)) { ?> value="<?php echo $HOST;?>"<?php } ?> />
</div>
<div>
<label for="<?php echo $ROOT_ID; ?>">Root directory</label>
<input type="text" name="<?php echo $ROOT_ID; ?>" id="<?php echo $ROOT_ID; ?>"<?php if (isset($ROOT)) { ?> value="<?php echo $ROOT;?>"<?php } ?> />
</div>
<div>
<label for="<?php echo $PREF_ID; ?>">Root directory prefix</label>
<input type="text" name="<?php echo $PREF_ID; ?>" id="<?php echo $PREF_ID; ?>"<?php if (isset($PREF)) { ?> value="<?php echo $PREF;?>"<?php } ?> />
</div>
<div>
<span class="basic_form_submit"><input type="submit" value="Create" /></span>
<span class="basic_form_clear"><input type="reset" value="Clear" /></span>
</div>
</form>
