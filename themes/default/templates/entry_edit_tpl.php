<?php if (isset($HAS_UPDATE_ERROR)): ?>
<h3><?php echo $UPDATE_ERROR_MESSAGE; ?></h3>
<?php endif; ?>

<div class="entry_preview">
<?php echo @$PREVIEW_DATA; ?>
</div>

<fieldset>
<form id="postform" method="post" action="<?php echo $FORM_ACTION; ?>" enctype="multipart/form-data" accept-charset="<?php echo DEFAULT_CHARSET;?>">
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
			<?php foreach ($BLOG_TAGS as $tag): ?>
				<option value="<?php echo $tag;?>"><?php echo $tag;?></option>
			<?php endforeach; ?>
		</select>
	</div>

	<?php if (! $PUBLISHED): ?>
	<div id="articlepath">
		<?php $title = _("The last part of the URL path for this article");?>
		<label class="basic_form_label" for="short_path" title="<?php echo $title;?>"><?php p_("Article path"); ?></label>
		<input id="short_path" name="short_path" title="<?php echo $title;?>" type="text" size="40" <?php 
		if (isset($URL)) { ?>value="<?php echo $URL; ?>" <?php } ?>/>
	</div>
	<?php endif; ?>

	<?php if (isset($GET_ABSTRACT)): ?>
	<div>
		<?php $title = _("An abstract or summary paragraph for this entry");?>
		<label class="basic_form_label" title="<?php echo $title;?>" for="abstract"><?php p_("Abstract"); ?></label>
		<input id="abstract" name="abstract" title="<?php echo $title;?>" type="text" size="40" <?php 
		if (isset($URL)) { ?>value="<?php echo $URL; ?>" <?php } ?>/>
	</div>
	<?php endif; ?>

	<?php 
	if (EventRegister::instance()->hasHandlers("posteditor", "ShowControls")) {
		EventRegister::instance()->activateEventFull($tmp=false, "posteditor", "ShowControls");
	} else {
		$use_js_editor = true;
	}
	if (isset($use_js_editor) && ! System::instance()->sys_ini->value('entryconfig', 'EditorOnBottom', 0)) {
		include("js_editor.php");
	}
	?>
	<div>
		<textarea id="body" name="body" accesskey="d" title="<?php p_("Post data");?>" rows="18" cols="40"><?php echo @$DATA; ?></textarea>
	</div>
	<?php
	if (isset($use_js_editor) && System::instance()->sys_ini->value('entryconfig', 'EditorOnBottom', 0)) {
		include("js_editor.php");
	}
	?>
	<fieldset id="entry_settings">
		<legend><?php p_("Entry settings");?><a href="#dummy">(-)</a></legend>
		<?php if (!$PUBLISHED): ?>
		<div>
			<?php $title = _("Publish this as an article instead of a regular blog entry");?>
			<label for="publisharticle" title="<?php echo $title;?>"><?php p_("Publish as article");?></label>
			<input type="checkbox" id="publisharticle" name="publisharticle" <?php if (isset($GET_SHORT_PATH)) { echo 'checked="checked"'; }?> />
		</div>
		<?php endif; ?>
		<?php if (!$PUBLISHED): ?>
		<div>
			<?php
			$title = _("Auto-publish this entry at this date");
			$date_string = $AUTO_PUBLISH_DATE ? strftime('%Y-%m-%dT%H:%M:%S', strtotime($AUTO_PUBLISH_DATE)) : '';
			?>
			<label for="autopublishdate" title="<?php echo $title;?>"><?php p_("Auto-publish date");?></label>
			<input type="datetime-local" id="autopublishdate" name="autopublishdate" value="<?php echo $date_string ; ?>" />
		</div>
		<? endif; ?>
		
	<?php if ($num_uploads = System::instance()->sys_ini->value("entryconfig", "AllowInitUpload", 1)): ?>
	<div class="upload_field">
		<?php for ($i = 1; $i <= $num_uploads; $i++): ?>
		<div>
			<label for="upload<?php echo $i;?>">Select file</label>
			<input type="file" name="upload[]" id="upload<?php echo $i;?>" />
		</div>
		<?php endfor; ?>
	</div>
	<?php endif; ?>
	
	<?php if ($ALLOW_ENCLOSURE || ! empty($ENCLOSURE)): /* Add optional enclosure box.*/?>
	<div>
	<?php $title = _('Enter the URL of the MP3 or other media file for this post.  If you are uploading the file to this post, you can enter just the filename.');?>
	<label for="enclosure" title="<?php echo $title;?>"><?php p_("Enclosure/Podcast URL");?></label>
	<input type="text" name="enclosure" id="enclosure" title="<?php echo $title;?>" value="<?php if (isset($ENCLOSURE)) echo $ENCLOSURE;?>" />
	</div>
	<?php endif; ?>
	
	<?php if (isset($STICKY)): /* Checkbox to make articles "sticky". */ ?>
	<div>
	<?php $title = _("Show a link to this in the articles panel of the sidebar");?>
	<label for="sticky" title="<?php echo $title;?>"><?php p_("Show in sidebar"); ?></label>
	<input id="sticky" name="sticky" title="<?php echo $title;?>" type="checkbox" <?php if ($STICKY) { ?> checked="checked" <?php } ?> />
	</div>
	<?php endif; ?>
	
	<div>
	<?php $title = _("Set the markup type for this entry.  Auto-markup is plain text with clickable URLs, LBcode is a dialect of the BBcode markup popular on web forums, and HTML is raw HTML code.");?>
	<label for="input_mode" title="<?php echo $title;?>"><?php p_("Markup type");?></label>
	<select id="input_mode" name="input_mode" title="<?php echo $title;?>">
	<option value="<?php echo MARKUP_NONE;?>"<?php if ($HAS_HTML == MARKUP_NONE){
	echo ' selected="selected"';}?>><?php p_("Auto-markup");?></option>
	<option value="<?php echo MARKUP_BBCODE;?>"<?php if ($HAS_HTML == MARKUP_BBCODE){
	echo ' selected="selected"';}?>><?php p_("LBcode");?></option>
	<option value="<?php echo MARKUP_HTML;?>"<?php if ($HAS_HTML == MARKUP_HTML){
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
	<input id="send_pingbacks" name="send_pingbacks" title="<?php echo $title;?>" type="checkbox" <?php if ( @$SEND_PINGBACKS === false ) { 
	?>checked="checked"<?php } ?> />
	</div>
	
	</fieldset>

<div class="threebutton">
<?php if ($PUBLISHED): ?>
<input name="post" id="post" type="submit" value="<?php p_("Save");?>" />
<?php else: ?>
<input name="post" id="post" type="submit" value="<?php p_("Publish");?>" />
<input name="draft" id="draft" type="submit" value="<?php p_("Save Draft");?>" />
<?php endif; ?>
<input name="preview" id="preview" type="submit" value="<?php p_("Preview");?>" />
</div>

</form>

</fieldset>
