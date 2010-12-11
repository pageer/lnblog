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
<div class="entry_subject">
<?php $title = _("Title or subject line for this entry");?>
<label for="subject" title="<?php echo $title;?>"><?php p_("Subject"); ?></label>
<input id="subject" name="subject" accesskey="s" title="<?php echo $title;?>" type="text" size="40" <?php 
if (isset($SUBJECT)) { ?>value="<?php echo $SUBJECT; ?> " <?php } ?> />
</div>
<div class="entry_tags">
<?php $title = _("Comma-separated list of topics");?>
<label for="tags" title="<?php echo $title;?>"><?php p_("Topics"); ?></label>
<input id="tags" name="tags" accesskey="t" title="<?php echo $title;?>" type="text" size="40" <?php 
if (isset($TAGS)) { ?>value="<?php echo $TAGS; ?>" <?php } ?>/>
<select id="tag_list" title="<?php p_("Add topic");?>">
<option value="" selected="selected"><?php p_("Add topic:");?></option>
<?php foreach ($BLOG_TAGS as $tag) { ?>
	<option value="<?php echo $tag;?>"><?php echo $tag;?></option>
<?php } ?>
</select>
</div>
<div id="articlepath">
<?php $title = _("The last part of the URL path for this article");?>
<label class="basic_form_label" for="short_path" title="<?php echo $title;?>"><?php p_("Article path"); ?></label>
<input id="short_path" name="short_path" title="<?php echo $title;?>" type="text" size="40" <?php 
if (isset($URL)) { ?>value="<?php echo $URL; ?>" <?php } ?>/>
</div>
<?php if (isset($GET_ABSTRACT)) { ?>
<div>
<?php $title = _("An abstract or summary paragraph for this entry");?>
<label class="basic_form_label" title="<?php echo $title;?>" for="abstract"><?php p_("Abstract"); ?></label>
<input id="abstract" name="abstract" title="<?php echo $title;?>" type="text" size="40" <?php 
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
<?php if (! isset($IS_PUBLISHED)) { ?>
<div>
<?php $title = _("Publish this as an article instead of a regular blog entry");?>
<label for="publisharticle" title="<?php echo $title;?>"><?php p_("Publish as article");?></label>
<input type="checkbox" id="publisharticle" <?php if (isset($GET_SHORT_PATH)) { echo 'checked="checked"'; }?> />
</div>
<?php } ?>
<?php
$num_uploads = $SYSTEM->sys_ini->value("entryconfig", "AllowInitUpload", 1);

if ($num_uploads > 0) {
?>
<div>
<?php $title = _("If this is checked, then when you add an item to be uploaded, a link or image tag for it will be added in the body of the entry at the current cursor location.");?>
<label for="adduploadlink" title="<?php echo $title;?>"><?php p_("Insert link when adding uploads");?></label>
<input type="checkbox" id="adduploadlink"  title="<?php echo $title;?>" <?php if (! empty($INSERT_LINKS)) echo 'checked="checked"'; ?> />
</div>
<?php
}

for ($i=1; $i<=$num_uploads; $i++) { ?>
<div>
<label for="upload<?php echo $i;?>"><?php p_("Upload file");?></label>
<input type="file" name="upload<?php echo $i;?>" id="upload<?php echo $i;?>" />
</div>
<?php } # End upload for
if ($ALLOW_ENCLOSURE || ! empty($ENCLOSURE)) { /* Add optional enclosure box.*/?>
<div>
<?php $title = _('Enter the URL of the MP3 or other media file for this post.  If you are uploading the file to this post, you can enter just the filename.');?>
<label for="enclosure" title="<?php echo $title;?>"><?php p_("Enclosure/Podcast URL");?></label>
<input type="text" name="enclosure" id="enclosure" title="<?php echo $title;?>" value="<?php if (isset($ENCLOSURE)) echo $ENCLOSURE;?>" />
</div>
<?php } # End enclosure
if (isset($STICKY)) { /* Checkbox to make articles "sticky". */ ?>
<div>
<?php $title = _("Show a link to this in the articles panel of the sidebar");?>
<label for="sticky" title="<?php echo $title;?>"><?php p_("Show in sidebar"); ?></label>
<input id="sticky" name="sticky" title="<?php echo $title;?>" type="checkbox" <?php if ($STICKY) { ?> checked="checked" <?php } ?> />
</div>
<?php } /* End sticky */ ?>
<div>
<?php $title = _("Set the markup type for this entry.  Auto-markup is plain text with clickable URLs, LBcode is a dialect of the BBcode markup popular on web forums, and HTML is raw HTML code.");?>
<label for="input_mode" title="<?php echo $title;?>"><?php p_("Markup type");?></label>
<select id="input_mode" name="input_mode" title="<?php echo $title;?>">
<option value="<?php echo MARKUP_NONE;?>"<?php if ($HAS_HTML == MARKUP_NONE){
echo ' selected="selected"';}?>><?php p_("Auto-markup");?></option>
<option value="<? echo MARKUP_BBCODE;?>"<?php if ($HAS_HTML == MARKUP_BBCODE){
echo ' selected="selected"';}?>><?php p_("LBcode");?></option>
<option value="<? echo MARKUP_HTML;?>"<?php if ($HAS_HTML == MARKUP_HTML){
echo ' selected="selected"';}?>><?php p_("HTML");?></option>
</select>
</div>
<div>
<?php $title = _("Allow readers to post comments on this entry");?>
<label for="comments" title="<?php echo $title;?>"><?php p_("Allow comments");?></label>
<input id="comments" name="comments" title="<?php echo $title;?>" type="checkbox" <?php if (! (isset($COMMENTS) && !$COMMENTS) ) { 
?>checked="checked"<?php } ?> />
</div>
<div>
<?php $title = _("Allow this entry to receive TrackBack pings from other blogs");?>
<label for="trackbacks" title="<?php echo $title;?>"><?php p_("Allow TrackBacks");?></label>
<input id="trackbacks" name="trackbacks" title="<?php echo $title;?>" type="checkbox" <?php if (! (isset($TRACKBACKS) && !$TRACKBACKS) ) { 
?>checked="checked"<?php } ?> />
</div>
<div>
<?php $title = _("Allow this entry to receive Pingback pings from other blogs");?>
<label for="pingbacks" title="<?php echo $title;?>"><?php p_("Allow Pingbacks");?></label>
<input id="pingbacks" name="pingbacks" title="<?php echo $title;?>" type="checkbox" <?php if (! (isset($PINGBACKS) && !$PINGBACKS) ) { 
?>checked="checked"<?php } ?> />
</div>
<div>
<?php $title = spf_("If this box is checked, then after the entry is published, %s will attempt to send Pingback pings to any URLs in the body of the post.", PACKAGE_NAME);?>
<label for="send_pingbacks" title="<?php echo $title;?>"><?php p_("Send Pingbacks after posting");?></label>
<input id="send_pingbacks" name="send_pingbacks" title="<?php echo $title;?>" type="checkbox" <?php if (! (isset($SEND_PINGBACKS) && !$SEND_PINGBACKS) ) { 
?>checked="checked"<?php } ?> />
</div>
</fieldset>
<div class="threebutton">
<input name="submit" id="submit" type="submit" value="<?php p_("Publish");?>" />
<?php if (! isset($IS_PUBLISHED)) { ?>
<input name="draft" id="draft" type="submit" value="<?php p_("Save Draft");?>" />
<?php } ?>
<input name="preview" id="preview" type="submit" value="<?php p_("Preview");?>" />
</div>
</form>
</fieldset>
