<h2><?php p_('File Upload');?></h2>
<p><?php pf_('Upload file to: <a href="%s">%s</a>', $TARGET_URL, $TARGET_URL);?></p>
<?php if (isset($UPLOAD_MESSAGE)) { ?>
<p><?php echo $UPLOAD_MESSAGE; ?></p>
<?php } ?>
<fieldset>
<form enctype="multipart/form-data" action="<?php echo $TARGET; ?>" method="post">
<?php for ($i = 1; $i <= $NUM_UPLOAD_FIELDS; $i++) { ?>
<div>
<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $MAX_SIZE; ?>" />
<label class="none" for="<?php echo $FILE.$i; ?>"><?php p_('Select file');?></label>
<input type="file" name="<?php echo $FILE.$i; ?>" id="<?php echo $FILE.$i; ?>" />
</div>
<?php } /* End foreach */ ?>
<div>
<span><input type="submit" name="submit" id="submit" value="<?php p_('Upload');?>" /></span>
<span><input type="reset" name="clear" id="clear" value="<?php p_('Clear');?>" /></span>
</div>
</form>
</fieldset>
