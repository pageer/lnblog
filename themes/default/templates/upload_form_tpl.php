<h2>File Upload</h2>
<p><?php echo $UPLOAD_MESSAGE; ?></p>
<form enctype="multipart/form-data" action="<?php echo $TARGET; ?>" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $MAX_SIZE; ?>" />
<div><label for="<?php echo $FILE; ?>">Select file</label><input type="file" name="<?php echo $FILE; ?>" id="<?php echo $FILE; ?>" /></div>
<div>
<span><input type="submit" name="submit" id="submit" value="Upload" /></span>
<span><input type="reset" name="clear" id="clear" value="Clear" /></span>
</div>
</form>
