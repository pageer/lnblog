<?php if (isset($HAS_UPDATE_ERROR)) { ?>
<h3><?php echo $UPDATE_ERROR_MESSAGE; ?></h3>
<?php } ?>
<fieldset>
<form id="postform" method="post">
<div>
<span class="basic_form_label"><label for="<?php echo $ARTICLE_POST_SUBJECT; ?>">Subject</label></span>
<span class="basic_form_data"><input id="<?php echo $ARTICLE_POST_SUBJECT; ?>" name="<?php echo $ARTICLE_POST_SUBJECT; ?>" type="text" <?php if (isset($SUBJECT)) { ?>value="<?php echo $SUBJECT; ?>" <?php } ?>/></span>
</div>
<?php if (isset($ARTICLE_POST_URL)) { ?>
<div>
<span class="basic_form_label"><label for="<?php echo $ARTICLE_POST_URL; ?>">Article path</label></span>
<span class="basic_form_data"><input id="<?php echo $ARTICLE_POST_URL; ?>" name="<?php echo $ARTICLE_POST_URL; ?>" type="text" <?php if (isset($URL)) { ?>value="<?php echo $URL; ?>" <?php } ?>/></span>
</div>
<?php } ?>
<div>
<textarea id="<?php echo $ARTICLE_POST_DATA; ?>" name="<?php echo $ARTICLE_POST_DATA; ?>" rows="18" cols="40">
<?php if (isset($DATA)) echo $DATA; ?>
</textarea>
</div>
<div>
<span class="check_label"><label for="mode_none">Auto-markup only</label></span>
<span class="check_data"><input id="mode_none" name="<?php echo $ARTICLE_POST_HTML; ?>" type="radio" <?php if ($HAS_HTML == MARKUP_NONE) { ?>checked="checked"<?php } ?> value="<?php echo MARKUP_NONE; ?>" /></span>
</div>
<div>
<span class="check_label"><label for="mode_bbcode">Use BBcode markup</label></span>
<span class="check_data"><input id="mode_bbcode" name="<?php echo $ARTICLE_POST_HTML; ?>" type="radio" <?php if ($HAS_HTML == MARKUP_BBCODE) { ?>checked="checked"<?php } ?> value="<?php echo MARKUP_BBCODE; ?>" /></span>
</div>
<div>
<span class="check_label"><label for="mode_html">Allow HTML markup</label></span>
<span class="check_data"><input id="mode_html" name="<?php echo $ARTICLE_POST_HTML; ?>" type="radio" <?php if ($HAS_HTML == MARKUP_HTML) { ?>checked="checked"<?php } ?> value="<?php echo MARKUP_HTML; ?>" /></span>
</div>
<div>
<span class="check_label"><label for="<?php echo $ARTICLE_POST_COMMENTS; ?>">Allow comments</label></span>
<span class="check_data"><input id="<?php echo $ARTICLE_POST_COMMENTS; ?>" name="<?php echo $ARTICLE_POST_COMMENTS; ?>" type="checkbox" <?php if (! (isset($COMMENTS) && !$COMMENTS) ) { ?>checked="checked"<?php } ?> /></span>
</div>
<div>
<span class="basic_form_submit"><input name="<?php echo $SUBMIT_ID; ?>" id="<?php echo $SUBMIT_ID; ?>" type="submit" value="Submit" /></span>
<span class="basic_form_clear"><input name="clear" id="clear" type="reset" value="Clear" /></span>
</div>
</form>
</fieldset>
<script type="text/javascript">
document.forms[0].elements['<?php echo $ARTICLE_POST_SUBJECT; ?>'].focus();
</script>
</div>
