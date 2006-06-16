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
global $SYSTEM;
if ($SYSTEM->sys_ini->value("entryconfig", "AllowInitUpload", 1) > 0) {
	$enctype = 'multipart/form-data';
} else {
	$enctype = 'application/x-www-form-urlencoded';
}

?>
<form id="postform" method="post" action="<?php echo $FORM_ACTION; ?>" enctype="<?php echo $enctype;?>">
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
<?php 
global $SYSTEM;
$num_uploads = $SYSTEM->sys_ini->value("entryconfig", "AllowInitUpload", 1);
for ($i=1; $i<=$num_uploads; $i++) { ?>
<div>
<label for="upload<?php echo $i;?>"><?php p_("Upload file");?></label>
<input type="file" name="upload<?php echo $i;?>" id="upload<?php echo $i;?>" />
</div>
<?php } ?>
<?php if (isset($STICKY)) { ?>
<div>
<label for="sticky"><?php p_("Make sticky (show in sidebar)"); ?></label>
<input id="sticky" name="sticky" type="checkbox" <?php if ($STICKY) { ?> checked="checked" <?php } ?> />
</div>
<?php } ?>
<div>
<label for="input_mode"><?php p_("Markup type");?></label>
<select id="input_mode" name="input_mode">
<option value="<?php echo MARKUP_NONE;?>"<?php if ($HAS_HTML == MARKUP_NONE){
echo ' selected="selected"';}?>><?php p_("Auto-markup");?></option>
<option value="<? echo MARKUP_BBCODE;?>"<?php if ($HAS_HTML == MARKUP_BBCODE){
echo ' selected="selected"';}?>><?php p_("LBcode");?></option>
<option value="<? echo MARKUP_HTML;?>"<?php if ($HAS_HTML == MARKUP_HTML){
echo ' selected="selected"';}?>><?php p_("HTML");?></option>
</select>
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
