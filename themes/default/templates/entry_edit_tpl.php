<?php if (isset($HAS_UPDATE_ERROR)) { ?>
<h3><?php echo $UPDATE_ERROR_MESSAGE; ?></h3>
<?php } ?>
<?php if (isset($PREVIEW_DATA)) {
	echo $PREVIEW_DATA;
} ?>
<fieldset>
<?php
global $SYSTEM;
if ($SYSTEM->sys_ini->value("entryconfig", "AllowInitUpload", 1) > 0) {
	$enctype = 'multipart/form-data';
} else {
	$enctype = 'application/x-www-form-urlencoded';
}
?>
<form id="postform" method="post" action="<?php echo $FORM_ACTION; ?>" enctype="<?php echo $enctype;?>" accept-charset="<?php echo DEFAULT_CHARSET;?>">
<div>
<label class="basic_form_label" for="subject"><?php p_("Subject"); ?></label>
<input id="subject" name="subject" accesskey="s" title="<?php p_("Subject");?>" type="text" size="40" <?php 
if (isset($SUBJECT)) { ?>value="<?php echo $SUBJECT; ?> " <?php } ?><?php
if (isset($GET_SHORT_PATH)) { ?>onblur="set_article_path();"<?php } ?>/>
</div>
<div>
<label class="basic_form_label" for="tags"><?php p_("Topics"); ?></label>
<input id="tags" name="tags" accesskey="t" title="<?php p_("Topics");?>" type="text" size="40" <?php 
if (isset($TAGS)) { ?>value="<?php echo $TAGS; ?>" <?php } ?>/>
<select id="tag_list" title="<?php p_("Add topic");?>" accesskey="p">
<option value="" selected="selected"><?php p_("Add topic:");?></option>
<?php foreach ($BLOG_TAGS as $tag) { ?>
	<option value="<?php echo $tag;?>"><?php echo $tag;?></option>
<?php } ?>
</select>
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
<?php 
global $EVENT_REGISTER;
global $SYSTEM;

if ($EVENT_REGISTER->hasHandlers("posteditor", "ShowControls")) {
	$EVENT_REGISTER->activateEventFull($tmp=false, "posteditor", "ShowControls");
} else {
	$use_js_editor = true;
}
if (isset($use_js_editor) && ! $SYSTEM->sys_ini->value('entryconfig', 'EditorOnBottom', 0)) {
	include("js_editor.php");
}
?>
<div>
<textarea id="body" name="body" accesskey="d" title="<?php p_("Post data");?>" rows="18" cols="40"><?php if (isset($DATA)) echo $DATA; ?></textarea>
</div>
<?php
if (isset($use_js_editor) && $SYSTEM->sys_ini->value('entryconfig', 'EditorOnBottom', 0)) {
	include("js_editor.php");
}
?>
<fieldset id="entry_settings">
<legend><?php p_("Entry settings");?><a href="#dummy">(-)</a></legend>
<?php
$num_uploads = $SYSTEM->sys_ini->value("entryconfig", "AllowInitUpload", 1);
for ($i=1; $i<=$num_uploads; $i++) { ?>
<div>
<label for="upload<?php echo $i;?>"><?php p_("Upload file");?></label>
<input type="file" name="upload<?php echo $i;?>" id="upload<?php echo $i;?>" />
</div>
<?php } # End upload for
if ($ALLOW_ENCLOSURE || ! empty($ENCLOSURE)) { /* Add optional enclosure box.*/?>
<div>
<label for="enclosure" title="<?php 
p_('Enter the URL of the MP3 or other media file for this post.  If you are uploading the file to this post, you can enter just the filename.');
?>"><?php p_("Enclosure/Podcast URL");?></label>
<input type="text" name="enclosure" id="enclosure" title="<?php 
p_('Enter the URL of the MP3 or other media file for this post.  If you are uploading the file to this post, you can enter just the filename.');
?>" value="<?php if (isset($ENCLOSURE)) echo $ENCLOSURE;?>" />
</div>
<?php } # End enclosure
if (isset($STICKY)) { /* Checkbox to make articles "sticky". */ ?>
<div>
<label for="sticky"><?php p_("Make sticky (show in sidebar)"); ?></label>
<input id="sticky" name="sticky" type="checkbox" <?php if ($STICKY) { ?> checked="checked" <?php } ?> />
</div>
<?php } /* End sticky */ ?>
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
<label for="comments"><?php p_("Allow comments");?></label>
<input id="comments" name="comments" type="checkbox" <?php if (! (isset($COMMENTS) && !$COMMENTS) ) { 
?>checked="checked"<?php } ?> />
</div>
<div>
<label for="trackbacks"><?php p_("Allow TrackBacks");?></label>
<input id="trackbacks" name="trackbacks" type="checkbox" <?php if (! (isset($TRACKBACKS) && !$TRACKBACKS) ) { 
?>checked="checked"<?php } ?> />
</div>
<div>
<label for="pingbacks"><?php p_("Allow Pingbacks");?></label>
<input id="pingbacks" name="pingbacks" type="checkbox" <?php if (! (isset($PINGBACKS) && !$PINGBACKS) ) { 
?>checked="checked"<?php } ?> />
</div>
<div>
<label for="send_pingbacks"><?php p_("Send Pingbacks after posting");?></label>
<input id="send_pingbacks" name="send_pingbacks" type="checkbox" <?php if (! (isset($SEND_PINGBACKS) && !$SEND_PINGBACKS) ) { 
?>checked="checked"<?php } ?> />
</div>
</fieldset>
<div class="threebutton">
<input name="submit" id="submit" type="submit" value="<?php p_("Submit");?>" />
<input name="preview" id="preview" type="submit" value="<?php p_("Preview");?>" />
<input name="clear" id="clear" type="reset" value="<?php p_("Clear");?>" />
</div>
</form>
</fieldset>
