<?php if (isset($HAS_UPDATE_ERROR)) { ?>
<h3><?php echo $UPDATE_ERROR_MESSAGE; ?></h3>
<?php } ?>
<fieldset>
<form id="postform" method="post" action="<?php echo $FORM_ACTION; ?>">
<div>
<label class="basic_form_label" for="<?php echo ENTRY_POST_SUBJECT; ?>">Subject</label>
<input id="<?php echo ENTRY_POST_SUBJECT; ?>" name="<?php echo ENTRY_POST_SUBJECT; ?>" type="text" <?php if (isset($SUBJECT)) { ?>value="<?php echo $SUBJECT; ?>" <?php } ?>/>
</div>
<div>
<textarea id="<?php echo ENTRY_POST_DATA; ?>" name="<?php echo ENTRY_POST_DATA; ?>" rows="18" cols="40">
<?php if (isset($DATA)) echo $DATA; ?>
</textarea>
</div>
<div>
<label for="mode_none">Auto-markup only</label>
<input id="mode_none" name="<?php echo ENTRY_POST_HTML; ?>" type="radio" <?php if ($HAS_HTML == MARKUP_NONE) { ?>checked="checked"<?php } ?> value="<?php echo MARKUP_NONE; ?>" />
</div>
<div>
<label for="mode_bbcode">Use <a onclick="javascript:window.open('<?php echo INSTALL_ROOT_URL; ?>/Readme.html#lbcode'); return false;" href="<?php echo INSTALL_ROOT_URL; ?>/Readme.html#lbcode">LBcode</a> markup</label>
<input id="mode_bbcode" name="<?php echo ENTRY_POST_HTML; ?>" type="radio" <?php if ($HAS_HTML == MARKUP_BBCODE) { ?>checked="checked"<?php } ?> value="<?php echo MARKUP_BBCODE; ?>" />
</div>
<div>
<label for="mode_html">Allow HTML markup</label>
<input id="mode_html" name="<?php echo ENTRY_POST_HTML; ?>" type="radio" <?php if ($HAS_HTML == MARKUP_HTML) { ?>checked="checked"<?php } ?> value="<?php echo MARKUP_HTML; ?>" />
</div>
<div>
<label for="<?php echo ENTRY_POST_COMMENTS; ?>">Allow comments</label>
<input id="<?php echo ENTRY_POST_COMMENTS; ?>" name="<?php echo ENTRY_POST_COMMENTS; ?>" type="checkbox" <?php if (! (isset($COMMENTS) && !$COMMENTS) ) { ?>checked="checked"<?php } ?> />
</div>
<div>
<span class="basic_form_submit"><input name="<?php echo $SUBMIT_ID; ?>" id="<?php echo $SUBMIT_ID; ?>" type="submit" value="Submit" /></span>
<span class="basic_form_clear"><input name="clear" id="clear" type="reset" value="Clear" /></span>
</div>
</form>
</fieldset>
<script type="text/javascript">
document.forms[0].elements['<?php echo ENTRY_POST_SUBJECT; ?>'].focus();
</script>
