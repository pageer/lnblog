<?php if (isset($HAS_UPDATE_ERROR)) { ?>
<h3><?php echo $UPDATE_ERROR_MESSAGE; ?></h3>
<?php } ?>
<?php if (isset($PREVIEW_DATA)) {
	echo $PREVIEW_DATA;
} ?>
<fieldset>
<?php
global $EVENT_REGISTER;
if ($EVENT_REGISTER->hasHandlers("posteditor", "ShowControls")) {
	$EVENT_REGISTER->activateEventFull($tmp=false, "posteditor", "ShowControls");
} else {
	include("js_editor.php");
}
?>
<form id="postform" method="post" action="<?php echo $FORM_ACTION; ?>">
<div>
<label class="basic_form_label" for="subject"><?php p_("Subject"); ?></label>
<input id="subject" name="subject" accesskey="s" type="text" size="40" <?php 
if (isset($SUBJECT)) { ?>value="<?php echo $SUBJECT; ?> " <?php } ?><?php
if (isset($GET_SHORT_PATH)) { ?>onblur="set_article_path();"<?php } ?>/>
</div>
<div>
<label class="basic_form_label" for="tags"><?php p_("Tags"); ?></label>
<input id="tags" name="tags" accesskey="t" type="text" size="40" <?php 
if (isset($TAGS)) { ?>value="<?php echo $TAGS; ?>" <?php } ?>/>
</div>
<?php if (isset($GET_SHORT_PATH)) { ?>
<div>
<label class="basic_form_label" for="short_path"><?php p_("Article path"); ?></label>
<input id="short_path" name="short_path" type="text" size="40" <?php 
if (isset($URL)) { ?>value="<?php echo $URL; ?>" <?php } ?>/>
</div>
<?php } ?>
<?php if (isset($GET_ABSTRACT)) { ?>
<div>
<label class="basic_form_label" for="abstract"><?php p_("Abstract"); ?></label>
<input id="abstract" name="abstract" type="text" size="40" <?php 
if (isset($URL)) { ?>value="<?php echo $URL; ?>" <?php } ?>/>
</div>
<?php } ?>
<div>
<textarea id="body" name="body" accesskey="d" rows="18" cols="40"><?php if (isset($DATA)) echo $DATA; ?></textarea>
</div>
<div>
<label for="mode_none"><?php p_("Auto-markup only"); ?></label>
<input id="mode_none" name="input_mode" type="radio" <?php if ($HAS_HTML == MARKUP_NONE) { 
?>checked="checked"<?php } ?> value="<?php echo MARKUP_NONE; ?>" />
</div>
<div>
<label for="mode_bbcode"><a onclick="javascript:window.open('<?php echo INSTALL_ROOT_URL; ?>/Readme.html#lbcode'); return false;" href="<?php echo INSTALL_ROOT_URL; ?>/Readme.html#lbcode"><?php p_("Use LBcode markup"); ?></a></label>
<input id="mode_bbcode" name="input_mode" type="radio" <?php if ($HAS_HTML == MARKUP_BBCODE) { 
?>checked="checked"<?php } ?> value="<?php echo MARKUP_BBCODE; ?>" />
</div>
<div>
<label for="mode_html"><?php p_("Allow HTML markup"); ?></label>
<input id="mode_html" name="input_mode" type="radio" <?php if ($HAS_HTML == MARKUP_HTML) { 
?>checked="checked"<?php } ?> value="<?php echo MARKUP_HTML; ?>" />
</div>
<div>
<label for="comments">Allow comments</label>
<input id="comments" name="comments" type="checkbox" <?php if (! (isset($COMMENTS) && !$COMMENTS) ) { 
?>checked="checked"<?php } ?> />
</div>
<div>
<label for="trackbacks">Allow trackbacks</label>
<input id="trackbacks" name="trackbacks" type="checkbox" <?php if (! (isset($TRACKBACKS) && !$TRACKBACKS) ) { 
?>checked="checked"<?php } ?> />
</div>
<div class="threebutton">
<input name="submit" id="submit" type="submit" value="<?php p_("Submit");?>" />
<input name="preview" id="preview" type="submit" value="<?php p_("Preview");?>" />
<input name="clear" id="clear" type="reset" value="<?php p_("Clear");?>" />
</div>
</form>
</fieldset>
<script type="text/javascript">
document.getElementById('subject').focus();
</script>
