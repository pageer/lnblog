<h2><?php echo $CONFIRM_TITLE; ?></h2>
<p><?php echo $CONFIRM_MESSAGE; ?></p>
<form method="post" action="<?php echo $CONFIRM_PAGE; ?>">
<div>
<input type="submit" id="<?php echo $OK_ID; ?>" name="<?php echo $OK_ID; ?>" value="<?php echo $OK_LABEL; ?>" />
<input type="submit" id="<?php echo $CANCEL_ID; ?>" name="<?php echo $CANCEL_ID; ?>" value="<?php echo $CANCEL_LABEL; ?>" />
<input type="hidden" name="confirm_form" value="1" />
<?php if (! empty($PASS_DATA_ID)) { ?>
<input type="hidden" id="<?php echo $PASS_DATA_ID; ?>" name="<?php echo $PASS_DATA_ID; ?>" value="<?php echo $PASS_DATA; ?>" />
<?php } ?>
</div>
</form>
