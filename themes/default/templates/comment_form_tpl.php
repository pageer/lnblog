<div id="commentsubmit">
<h3>Add your comments</h3>
<p>A comment body is required.  No HTML code allowed.  URLs starting with 
http:// or ftp:// will be automatically converted to hyperlinks.  
<?php if(!COMMENT_EMAIL_VIEW_PUBLIC){ ?>Your e-mail address will not be displayed.<?php } ?></p>

<fieldset>
<form id="commentform" method="post" action="index.php">
<div>
<label class="basic_form_label" for="<?php echo COMMENT_POST_SUBJECT; ?>"><em>S</em>ubject</label>
<input style="width: 70%" id="<?php echo COMMENT_POST_SUBJECT; ?>" name="<?php echo COMMENT_POST_SUBJECT; ?>" accesskey="s" type="text" />
</div>
<div>
<textarea id="<?php echo COMMENT_POST_DATA; ?>" name="<?php echo COMMENT_POST_DATA; ?>" accesskey="d" rows="10" cols="20"></textarea>
</div>
<div>
<label style="width: 40%" for="<?php echo COMMENT_POST_NAME; ?>"><em>N</em>ame</label>
<input id="<?php echo COMMENT_POST_NAME; ?>" name="<?php echo COMMENT_POST_NAME; ?>" accesskey="n" type="text" <?php if (COOKIE(COMMENT_POST_NAME)) echo 'value="'.COOKIE(COMMENT_POST_NAME).'" '; ?>/>
</div>
<div>
<label style="width: 40%" for="<?php echo COMMENT_POST_URL; ?>"><em>H</em>omepage</label>
<input id="<?php echo COMMENT_POST_URL; ?>" name="<?php echo COMMENT_POST_URL; ?>" accesskey="h" type="text" <?php if (COOKIE(COMMENT_POST_URL)) echo 'value="'.COOKIE(COMMENT_POST_URL).'" '; ?>/>
</div>
<div>
<label style="width: 40%" for="<?php echo COMMENT_POST_EMAIL; ?>"><em>E</em>-Mail</label>
<input id="<?php echo COMMENT_POST_EMAIL; ?>" name="<?php echo COMMENT_POST_EMAIL; ?>" accesskey="e" type="text" <?php if (COOKIE(COMMENT_POST_EMAIL)) echo 'value="'.COOKIE(COMMENT_POST_EMAIL).'" '; ?>/>
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
