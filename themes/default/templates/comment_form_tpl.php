<div id="commentsubmit">
<h3>Add your comments</h3>
<p>You must enter something in the comment body.  No HTML code allowed.  URLs starting with http:// or ftp:// will be automatically converted to hyperlinks.  Spam and other junk comments will be deleted.</p>
<fieldset>
<form id="commentform" method="post" action="index.php">
<div>
<label class="basic_form_label" for="<?php echo COMMENT_POST_SUBJECT; ?>">Subject</label>
<input style="width: 70%" id="<?php echo COMMENT_POST_SUBJECT; ?>" name="<?php echo COMMENT_POST_SUBJECT; ?>" type="text" />
</div>
<div>
<textarea id="<?php echo COMMENT_POST_DATA; ?>" name="<?php echo COMMENT_POST_DATA; ?>" rows="10" cols="20"></textarea>
</div>
<div>
<label style="width: 40%" for="<?php echo COMMENT_POST_NAME; ?>">Name</label>
<input id="<?php echo COMMENT_POST_NAME; ?>" name="<?php echo COMMENT_POST_NAME; ?>" type="text" <?php if (COOKIE(COMMENT_POST_NAME)) echo 'value="'.COOKIE(COMMENT_POST_NAME).'" '; ?>/>
</div>
<div>
<label style="width: 40%" for="<?php echo COMMENT_POST_URL; ?>">Homepage</label>
<input id="<?php echo COMMENT_POST_URL; ?>" name="<?php echo COMMENT_POST_URL; ?>" type="text" <?php if (COOKIE(COMMENT_POST_URL)) echo 'value="'.COOKIE(COMMENT_POST_URL).'" '; ?>/>
</div>
<div>
<label style="width: 40%" for="<?php echo COMMENT_POST_EMAIL; ?>">E-Mail</label>
<input id="<?php echo COMMENT_POST_EMAIL; ?>" name="<?php echo COMMENT_POST_EMAIL; ?>" type="text" <?php if (COOKIE(COMMENT_POST_EMAIL)) echo 'value="'.COOKIE(COMMENT_POST_EMAIL).'" '; ?>/>
</div>
<div>
<label for="<?php echo COMMENT_POST_REMEMBER; ?>">Remember me</label>
<input id="<?php echo COMMENT_POST_REMEMBER; ?>" name="<?php echo COMMENT_POST_REMEMBER; ?>" type="checkbox" checked="checked" />
</div>
<div>
<span class="basic_form_submit"><input name="<?php echo $SUBMIT_ID; ?>" id="<?php echo $SUBMIT_ID; ?>" type="submit" value="Submit" /></span>
<span class="basic_form_clear"><input name="clear" id="clear" type="reset" value="Clear" /></span>
</div>
</form>
</fieldset>
</div>
