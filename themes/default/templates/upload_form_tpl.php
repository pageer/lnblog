<h2><?php p_('File Upload');?></h2>
<p><?php pf_('Upload file to: <a href="%s">%s</a>', $TARGET_URL, $TARGET_URL);?></p>
<p><?php echo $UPLOAD_MESSAGE; ?></p>
<fieldset>
<form enctype="multipart/form-data" action="<?php echo $TARGET; ?>" method="post">
<div>
<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $MAX_SIZE; ?>" />
<label class="none" for="<?php echo $FILE; ?>"><?php p_('Select file');?></label>
<input type="file" name="<?php echo $FILE; ?>" id="<?php echo $FILE; ?>" />
</div>
<div>
<span><input type="submit" name="submit" id="submit" value="<?php p_('Upload');?>" /></span>
<span><input type="reset" name="clear" id="clear" value="<?php p_('Clear');?>" /></span>
</div>
</form>
</fieldset>
