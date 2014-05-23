<h2><?php echo $FORM_TITLE; ?></h2>
<?php if (isset($FORM_MESSAGE)) { ?>
<p><?php echo $FORM_MESSAGE; ?></p>
<?php } ?>
<form method="post" action="<?php echo $FORM_ACTION; ?>">
<?php if (isset($UNAME)) { ?>
<div>
<label for="<?php echo $UNAME; ?>"><?php p_('Username'); ?></label>
<input type="text" id="<?php echo $UNAME; ?>" name="<?php echo $UNAME; ?>" <?php if (isset($UNAME_VALUE)) { echo 'value="'.$UNAME_VALUE.'" '; } if (isset($DISABLE_UNAME)) { echo 'readonly="readonly" style="background-color: #E0E0E0;"'; } ?> />
</div>
<?php } ?>
<div>
<label for="passwd"><?php p_('Password'); ?></label>
<input type="password" id="passwd" name="passwd" <?php if (isset($PWD_VALUE)) echo 'value="'.$PWD_VALUE.'" '; ?>/>
</div>
<div>
<label for="confirm"><?php p_('Confirm'); ?></label>
<input type="password" id="confirm" name="confirm" <?php if (isset($CONFIRM_VALUE)) echo 'value="'.$CONFIRM_VALUE.'" '; ?>/>
</div>
<div>
<label for="fullname"><?php p_('Real name');?></label>
<input type="text" id="fullname" name="fullname" <?php if (isset($FULLNAME_VALUE)) echo 'value="'.$FULLNAME_VALUE.'" '; ?>/>
</div>
<div>
<label for="email"><?php p_('E-Mail');?></label>
<input type="text" id="email" name="email" <?php if (isset($EMAIL_VALUE)) echo 'value="'.$EMAIL_VALUE.'" '; ?>/>
</div>
<div>
<label for="homepage"><?php p_('Homepage');?></label>
<input type="text" id="homepage" name="homepage" <?php if (isset($HOMEPAGE_VALUE)) echo 'value="'.$HOMEPAGE_VALUE.'" '; ?>/>
</div>
<div>
<label for="profile_url"><?php p_('Custom profile URL (optional)');?></label>
<input type="text" id="profile_url" name="profile_url" <?php if (isset($PROFILEPAGE_VALUE)) echo 'value="'.$PROFILEPAGE_VALUE.'" '; ?>/>
</div>
<?php foreach ($CUSTOM_FIELDS as $key=>$val) { ?>
<div>
<label for="<?php echo $key;?>"><?php echo htmlspecialchars($val);?></label>
<input type="text" id="<?php echo $key;?>" name="<?php echo $key;?>" <?php if (isset($CUSTOM_VALUES[$key])) echo 'value="'.htmlspecialchars($CUSTOM_VALUES[$key]).'" '; ?>/>
</div>
<?php } ?>
<?php if ( isset($UPLOAD_LINK) && isset($PROFILE_EDIT_LINK) ) { ?>
<p style="text-align: center">
<a href="<?php echo $PROFILE_EDIT_LINK;?>"><?php echo $PROFILE_EDIT_DESC;?></a><br />
<a href="<?php echo $UPLOAD_LINK;?>"><?php echo $UPLOAD_DESC;?></a>
</p>
<?php } ?>
<div>
<span class="basic_form_submit"><input type="submit" value="<?php p_('Submit');?>" /></span>
<span class="basic_form_clear"><input type="reset" value="<?php p_('Clear');?>" /></span></div>
</form>
<script type="text/javascript">
<!--
<?php if (! isset($UNAME)) { ?>
var elem = document.getElementById('<?php echo $PWD; ?>');
elem.focus();
<?php } else { ?>
var elem = document.getElementById('<?php echo $UNAME; ?>');
elem.focus();
<?php } ?>
-->
</script>
