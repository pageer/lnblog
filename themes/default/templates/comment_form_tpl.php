<h3>Add your comments</h3>
<p>You must enter something in the comment body.  No HTML code allowed.  URLs starting with http:// or ftp:// will be automatically converted to hyperlinks.  Spam and other junk comments will be deleted.</p>
<div id="commentsubmit">
<fieldset>
<form id="commentform" method="post" action="index.php">
<div>
<span class="basic_form_label"><label for="<?php echo COMMENT_POST_NAME; ?>">Name</label></span>
<span class="basic_form_data"><input id="<?php echo COMMENT_POST_NAME; ?>" name="<?php echo COMMENT_POST_NAME; ?>" type="text" <?php if (COOKIE(COMMENT_POST_NAME)) echo 'value="'.COOKIE(COMMENT_POST_NAME).'" '; ?>/></span>
</div>
<div>
<span class="basic_form_label"><label for="<?php echo COMMENT_POST_URL; ?>">Homepage</label></span>
<span class="basic_form_data"><input id="<?php echo COMMENT_POST_URL; ?>" name="<?php echo COMMENT_POST_URL; ?>" type="text" <?php if (COOKIE(COMMENT_POST_URL)) echo 'value="'.COOKIE(COMMENT_POST_URL).'" '; ?>/></span>
</div>
<div>
<span class="basic_form_label"><label for="<?php echo COMMENT_POST_EMAIL; ?>">E-Mail</label></span>
<span class="basic_form_data"><input id="<?php echo COMMENT_POST_EMAIL; ?>" name="<?php echo COMMENT_POST_EMAIL; ?>" type="text" <?php if (COOKIE(COMMENT_POST_EMAIL)) echo 'value="'.COOKIE(COMMENT_POST_EMAIL).'" '; ?>/></span>
</div>
<div>
<span class="basic_form_label"><label for="<?php echo COMMENT_POST_REMEMBER; ?>">Remember me</label></span>
<span class="basic_form_data"><input id="<?php echo COMMENT_POST_REMEMBER; ?>" name="<?php echo COMMENT_POST_REMEMBER; ?>" type="checkbox" checked="checked" /></span>
</div>

<div>
<span class="basic_form_label"><label for="<?php echo COMMENT_POST_SUBJECT; ?>">Subject</label></span>
<span class="basic_form_data"><input id="<?php echo COMMENT_POST_SUBJECT; ?>" name="<?php echo COMMENT_POST_SUBJECT; ?>" type="text" /></span>
</div>
<div>
<textarea id="<?php echo COMMENT_POST_DATA; ?>" name="<?php echo COMMENT_POST_DATA; ?>" rows="10" cols="20"></textarea>
</div>
<div>
<span class="basic_form_submit"><input name="<?php echo $SUBMIT_ID; ?>" id="<?php echo $SUBMIT_ID; ?>" type="submit" value="Submit" /></span>
<span class="basic_form_clear"><input name="clear" id="clear" type="reset" value="Clear" /></span>
</div>
</form>
</fieldset>
</div>
