<h2><?php echo $FORM_TITLE; ?></h2>
<?php if (isset($FORM_MESSAGE)) { ?>
<p><?php echo $FORM_MESSAGE; ?></p>
<?php } ?>
<form method="post" action="<?php echo $FORM_ACTION; ?>">
<div><label for="<?php echo $UNAME; ?>">Username</label><input type="text" id="<?php echo $UNAME; ?>" name="<?php echo $UNAME; ?>" <?php if (isset($UNAME_VALUE)) echo 'value="'.$UNAME_VALUE.'" '; ?>/></div>
<div><label for="<?php echo $PWD; ?>">Password</label><input type="password" id="<?php echo $PWD; ?>" name="<?php echo $PWD; ?>" <?php if (isset($PWD_VALUE)) echo 'value="'.$PWD_VALUE.'" '; ?>/></div>
<div><span class="basic_form_submit"><input type="submit" value="Submit" /></span><span class="basic_form_clear"><input type="reset" value="Clear" /></span></div>
</form>
<script type="text/javascript">
<!--
document.forms[0].elements['<?php echo $UNAME; ?>'].focus();
-->
</script>

