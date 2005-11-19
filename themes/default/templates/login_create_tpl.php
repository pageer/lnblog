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
<label for="<?php echo $PWD; ?>"><?php p_('Password'); ?></label>
<input type="password" id="<?php echo $PWD; ?>" name="<?php echo $PWD; ?>" <?php if (isset($PWD_VALUE)) echo 'value="'.$PWD_VALUE.'" '; ?>/>
</div>
<div>
<label for="<?php echo $CONFIRM; ?>"><?php p_('Confirm'); ?></label>
<input type="password" id="<?php echo $CONFIRM; ?>" name="<?php echo $CONFIRM; ?>" <?php if (isset($CONFIRM_VALUE)) echo 'value="'.$CONFIRM_VALUE.'" '; ?>/>
</div>
<div>
<label for="<?php echo $FULLNAME; ?>"><?php p_('Real name');?></label>
<input type="text" id="<?php echo $FULLNAME; ?>" name="<?php echo $FULLNAME; ?>" <?php if (isset($FULLNAME_VALUE)) echo 'value="'.$FULLNAME_VALUE.'" '; ?>/>
</div>
<div>
<label for="<?php echo $EMAIL; ?>"><?php p_('E-Mail');?></label>
<input type="text" id="<?php echo $EMAIL; ?>" name="<?php echo $EMAIL; ?>" <?php if (isset($EMAIL_VALUE)) echo 'value="'.$EMAIL_VALUE.'" '; ?>/>
</div>
<div>
<label for="<?php echo $HOMEPAGE; ?>"><?php p_('Homepage');?></label>
<input type="text" id="<?php echo $HOMEPAGE; ?>" name="<?php echo $HOMEPAGE; ?>" <?php if (isset($HOMEPAGE_VALUE)) echo 'value="'.$HOMEPAGE_VALUE.'" '; ?>/>
</div>
<div>
<span class="basic_form_submit"><input type="submit" value="<?php p_('Create');?>" /></span>
<span class="basic_form_clear"><input type="reset" value="<?php p_('Clear');?>" /></span></div>
</form>
<script type="text/javascript">
<!--
<?php if (isset($DISABLE_UNAME)) { ?>
var elem = document.getElementById('<?php echo $PWD; ?>');
elem.focus();
<?php } else { ?>
var elem = document.getElementById('<?php echo $UNAME; ?>');
elem.focus();
<?php } ?>
-->
</script>
