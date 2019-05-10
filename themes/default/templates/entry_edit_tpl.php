<?php if (isset($BLOG_URL_ROOTREL)): ?>
<script type="application/javascript">
    window.AJAX_URL = '<?php echo $BLOG_URL_ROOTREL?>';
</script>
<?php endif ?>

<?php if (isset($HAS_UPDATE_ERROR)): ?>
<h3><?php echo $UPDATE_ERROR_MESSAGE?></h3>
<?php endif; ?>

<div class="entry_preview" <?php echo !empty($PREVIEW_DATA) ? 'style="display:block"' : ''?>>
	<a href="#" class="preview-close">[<?php p_('Close Preview')?>]</a>
	<div class="preview-text"><?php echo @$PREVIEW_DATA?></div>
</div>

<fieldset>
<form id="postform" method="post" action="<?php echo $FORM_ACTION?>"
	  enctype="multipart/form-data" accept-charset="<?php echo DEFAULT_CHARSET?>"

	<?php echo !empty($PREVIEW_DATA) ? 'style="display:none"' : ''?>>

    <?php if (isset($ENTRYID)): ?>
        <input type="hidden" name="entryid" value="<?php echo $ENTRYID?>" />
    <?php endif ?>

	<div class="entry_subject">
		<?php
		$title = _("Title or subject line for this entry");
		$subject_val = isset($SUBJECT) ? ('value="' . $SUBJECT . '"') : '';
		?>
		<input placeholder="<?php p_("Entry title or subject")?>" id="subject" name="subject"
		       accesskey="s" title="<?php echo $title?>" type="text" size="40" <?php echo $subject_val?> />
	</div>

	<div class="entry_tags">
		<?php
		$title = _("Comma-separated list of tagss");
		$tags_val = isset($TAGS) ? ('value="' . $TAGS . '"') : '';
		?>
		<input placeholder="<?php p_("Tags for this entry (comma-separated list)")?>" id="tags" name="tags"
		       accesskey="t" title="<?php echo $title?>" type="text" size="40" <?php echo $tags_val?> />
		<select id="tag_list" title="<?php p_("Add tag")?>">
			<option value="" selected="selected"><?php p_("Add tag:")?></option>
			<?php foreach ($BLOG_TAGS as $tag): ?>
				<option value="<?php echo $tag?>"><?php echo $tag?></option>
			<?php endforeach; ?>
		</select>
	</div>

	<?php if (isset($GET_ABSTRACT)): ?>
	<div>
		<?php $title = _("An abstract or summary paragraph for this entry");?>
		<label class="basic_form_label" title="<?php echo $title;?>" for="abstract"><?php p_("Abstract"); ?></label>
		<input id="abstract" name="abstract" title="<?php echo $title;?>" type="text" size="40" <?php echo $url_val?> />
	</div>
	<?php endif; ?>

	<?php
	EventRegister::instance()->activateEventFull($tmp=false, "posteditor", "ShowControls");
	if (! System::instance()->sys_ini->value('entryconfig', 'EditorOnBottom', 0)) {
		include $this->getTemplatePath("js_editor.php");
	}
	?>
	<div>
		<textarea id="body" name="body" accesskey="d" rows="18" cols="40"><?php echo @$DATA?></textarea>
	</div>
	<?php
	if (System::instance()->sys_ini->value('entryconfig', 'EditorOnBottom', 0)) {
		include $this->getTemplatePath("js_editor.php");
	}
	?>

	<div class="threebutton">
		<?php if ($PUBLISHED): ?>
		<input name="post" id="post" type="submit" value="<?php p_("Save")?>" />
		<?php else: ?>
		<input name="post" id="post" type="submit" value="<?php p_("Publish")?>" />
		<input name="draft" id="draft" type="submit" value="<?php p_("Save Draft")?>" />
		<?php endif; ?>
		<input name="preview" id="preview" type="submit" value="<?php p_("Preview")?>" />
	</div>


	<fieldset id="entry_settings">
		<legend><?php p_("Entry settings");?></legend>
		<div class="settings">

			<div class="left-col">
				<?php if (!$PUBLISHED): ?>
				<div>
					<?php $title = _("Publish this as an article instead of a regular blog entry");?>
					<input type="checkbox" class="checktoggle" id="publisharticle" name="publisharticle" data-for="short-path" value="1" <?php echo !empty($PUBLISHARTICLE) ? 'checked="checked"' : ''?> />
					<label for="publisharticle" title="<?php echo $title;?>"><?php p_("Publish as article")?></label>
					<?php $url_val = isset($URL) ? ('value="' . $URL . '"') : ''; ?>
					<input id="short_path" name="short_path" title="<?php p_("The last part of the URL path for this article");?>"
						   placeholder="<?php p_("Article path")?>" type="text" size="30" <?php echo $url_val?> />
				</div>
				<?php endif; ?>

				<?php if (!$PUBLISHED || $ARTICLE): ?>
				<div class="sticky-toggle">
					<?php $title = _("Show a link to this in the articles panel of the sidebar");?>
					<input id="sticky" name="sticky" title="<?php echo $title?>" type="checkbox" <?php echo !empty($STICKY) ? 'checked="checked"' : ''?> />
					<label for="sticky" title="<?php echo $title?>"><?php p_("Show in sidebar")?></label>
				</div>
                <?php endif; ?>

				<?php if (!$PUBLISHED): ?>
				<div>
					<?php
					$title = _("Auto-publish this entry at this date");
					$date_string = $AUTO_PUBLISH_DATE ? date('Y-m-d h:i a', strtotime($AUTO_PUBLISH_DATE)) : '';
					?>
					<input type="checkbox" class="checktoggle" id="autopublish" name="autopublish" data-for="autopublishdate" value="1" <?php echo $AUTO_PUBLISH_DATE ? "checked" : ''?> />
					<label for="autopublish" title="<?php echo $title?>"><?php p_("Auto-publish")?></label>
					<input type="text" id="autopublishdate" name="autopublishdate" value="<?php echo $date_string?>" />
				</div>
				<?php endif; ?>

				<?php if ($ALLOW_ENCLOSURE || ! empty($ENCLOSURE)): /* Add optional enclosure box.*/?>
				<div>
					<?php $title = _('Enter the URL of the MP3 or other media file for this post.  If you are uploading the file to this post, you can enter just the filename.');?>
					<input type="checkbox" class="checktoggle" id="hasenclosure" name="hasenclosure" data-for="enclosure" value="1" <?php echo empty($ENCLOSURE) ? '' : "checked"?> />
					<label for="hasenclosure" title="<?php echo $title?>"><?php p_("Enclosure/Podcast")?></label>
					<input type="text" name="enclosure" size="30" placeholder="<?php p_('File URL')?>" id="enclosure" title="<?php echo $title?>" value="<?php echo isset($ENCLOSURE) ? $ENCLOSURE : ''?>" />
				</div>
				<?php endif; ?>

				<div>
				<?php $title = _("Set the markup type for this entry.  Auto-markup is plain text with clickable URLs, LBcode is a dialect of the BBcode markup popular on web forums, and HTML is raw HTML code.");?>
				<label for="input_mode" title="<?php echo $title?>"><?php p_("Markup type")?></label>
				<select id="input_mode" name="input_mode" title="<?php echo $title?>">
				<?php foreach (TextProcessor::getAvailableFilters() as $filter): ?>
				<option value="<?php echo $filter->filter_id?>" <?php echo ($HAS_HTML == $filter->filter_id)  ?' selected="selected"' : ''?>><?php p_($filter->filter_name)?></option>
				<?php endforeach; ?>
				</select>
				</div>
			</div>

			<div class="right-col">
				<div>
				<?php $title = _("Allow readers to post comments on this entry");?>
				<input id="comments" name="comments" title="<?php echo $title?>" type="checkbox" <?php echo !empty($COMMENTS) ? 'checked="checked"' : ''?> />
				<label for="comments" title="<?php echo $title?>"><?php p_("Allow comments")?></label>
				</div>

				<div>
                <?php $title = _("Allow this entry to receive TrackBack pings from other blogs");?>
				<input id="trackbacks" name="trackbacks" title="<?php echo $title?>" type="checkbox" <?php echo !empty($TRACKBACKS) ? 'checked="checked"' : ''?> />
				<label for="trackbacks" title="<?php echo $title?>"><?php p_("Allow TrackBacks")?></label>
				</div>

				<div>
				<?php $title = _("Allow this entry to receive Pingback pings from other blogs");?>
				<input id="pingbacks" name="pingbacks" title="<?php echo $title?>" type="checkbox" <?php echo !empty($PINGBACKS) ? 'checked="checked"' : ''?> />
				<label for="pingbacks" title="<?php echo $title;?>"><?php p_("Allow Pingbacks")?></label>
				</div>

				<div>
				<?php $title = spf_("If this box is checked, then after the entry is published, %s will attempt to send Pingback pings to any URLs in the body of the post.", PACKAGE_NAME);?>
				<input id="send_pingbacks" name="send_pingbacks" title="<?php echo $title?>" type="checkbox" <?php echo !empty($SEND_PINGBACKS) ? 'checked="checked"' : ''?> />
				<label for="send_pingbacks" title="<?php echo $title?>"><?php p_("Send Pingbacks after posting")?></label>
				</div>
			</div>

			<div class="bottom-row">
                <?php if ($ENTRY_ATTACHMENTS): ?>
                <?php endif ?>
                <a name="entry-attachments" href="#" class="attachment-list-toggle" <?php
                    echo empty($ENTRY_ATTACHMENTS) ? 'style="display: none"' : ''
                ?>><?php p_("Entry attachments") ?></a>
                <ul class="entry-attachments attachment-list" <?php
                    echo empty($ENTRY_ATTACHMENTS) ? 'style="display: none"' : ''
                ?>>
                    <?php foreach ($ENTRY_ATTACHMENTS as $attachment): ?>
                        <li class="attachment" data-file="<?php echo $this->escape($attachment->getName())?>">
                            <?php echo $this->escape($attachment->getName()) ?>
                        </li>
                    <?php endforeach ?>
                </ul>
                <?php if ($BLOG_ATTACHMENTS): ?>
                    <a name="blog-attachments" href="#" class="attachment-list-toggle"><?php p_("Blog attachments") ?></a>
                    <ul class="blog-attachments attachment-list">
                        <?php foreach ($BLOG_ATTACHMENTS as $attachment): ?>
                            <li class="attachment" data-file="<?php echo $this->escape($attachment->getName())?>">
                                <?php echo $this->escape($attachment->getName()) ?>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
				<?php if ($num_uploads = System::instance()->sys_ini->value("entryconfig", "AllowInitUpload", 1)): ?>
				<div id="fileupload" class="upload_field">
					<div>
						<label for="upload1">Select file</label>
						<input type="file" name="upload[]" id="upload1" />
					</div>
				</div>
                <div id="filedrop" class="dropzone"></div>
				<?php endif; ?>
			</div>
		</div>
	</fieldset>

</form>

</fieldset>
