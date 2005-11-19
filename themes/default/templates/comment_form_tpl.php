<div id="commentsubmit">
<?php if (isset($PARENT_TITLE)) { ?>
<h3><?php pf_("Add your comments on %s", $PARENT_TITLE); ?></h3>
<?php } else { ?>
<h3><?php p_("Add your comments"); ?></h3>
<?php } ?>
<p><?php p_("A comment body is required.  No HTML code allowed.  URLs starting with 
http:// or ftp:// will be automatically converted to hyperlinks."); ?>
<?php if(!COMMENT_EMAIL_VIEW_PUBLIC){ p_("Your e-mail address will not be displayed."); } ?></p>

<fieldset>
<form id="commentform" method="post" action="index.php">
<div>
<label class="basic_form_label" for="<?php echo COMMENT_POST_SUBJECT; ?>"><?php p_("Subject"); ?></label>
<input style="width: 70%" id="<?php echo COMMENT_POST_SUBJECT; ?>" name="<?php echo COMMENT_POST_SUBJECT; ?>" accesskey="s" type="text" />
</div>
<div>
<textarea id="<?php echo COMMENT_POST_DATA; ?>" name="<?php echo COMMENT_POST_DATA; ?>" accesskey="d" rows="10" cols="20"></textarea>
</div>
<div>
<label style="width: 40%" for="<?php echo COMMENT_POST_NAME; ?>"><?php p_("Name"); ?></label>
<input id="<?php echo COMMENT_POST_NAME; ?>" name="<?php echo COMMENT_POST_NAME; ?>" accesskey="n" type="text" <?php 
if (POST(COMMENT_POST_NAME)) {
	echo 'value="'.POST(COMMENT_POST_NAME).'" '; 
} elseif (COOKIE(COMMENT_POST_NAME)) {
	echo 'value="'.COOKIE(COMMENT_POST_NAME).'" '; 
}
?>/>
</div>
<div>
<label style="width: 40%" for="<?php echo COMMENT_POST_URL; ?>"><?php p_("Homepage"); ?></label>
<input id="<?php echo COMMENT_POST_URL; ?>" name="<?php echo COMMENT_POST_URL; ?>" accesskey="h" type="text" <?php 
if (POST(COMMENT_POST_URL)) {
	echo 'value="'.POST(COMMENT_POST_URL).'" '; 
} elseif (COOKIE(COMMENT_POST_URL)) {
	echo 'value="'.COOKIE(COMMENT_POST_URL).'" '; 
}
?>/>
</div>
<div>
<label style="width: 40%" for="<?php echo COMMENT_POST_EMAIL; ?>"><?php p_("E-Mail"); ?></label>
<input id="<?php echo COMMENT_POST_EMAIL; ?>" name="<?php echo COMMENT_POST_EMAIL; ?>" accesskey="e" type="text" <?php 
if (POST(COMMENT_POST_EMAIL)) {
	echo 'value="'.POST(COMMENT_POST_EMAIL).'" '; 
} elseif (COOKIE(COMMENT_POST_EMAIL)) {
	echo 'value="'.COOKIE(COMMENT_POST_EMAIL).'" '; 
}
?>/>
</div>
<div>
<label for="<?php echo COMMENT_POST_REMEMBER; ?>"><?php p_("Remember me"); ?></label>
<input id="<?php echo COMMENT_POST_REMEMBER; ?>" name="<?php echo COMMENT_POST_REMEMBER; ?>" type="checkbox" checked="checked" />
</div>
<div>
<span class="basic_form_submit"><input name="<?php echo $SUBMIT_ID; ?>" id="<?php echo $SUBMIT_ID; ?>" type="submit" value="<?php p_("Submit"); ?>" /></span>
<span class="basic_form_clear"><input name="clear" id="clear" type="reset" value="<?php p_("Clear"); ?>" /></span>
</div>
</form>
</fieldset>
</div>
