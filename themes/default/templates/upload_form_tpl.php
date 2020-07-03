<h2><?php p_('File Upload');?></h2>

<p><?php pf_('Upload file to: <a href="%s">%s</a>', $TARGET_URL, $TARGET_URL);?></p>

<?php if (isset($UPLOAD_MESSAGE)): ?>
<p><?php echo $UPLOAD_MESSAGE; ?></p>
<?php endif; ?>

<fieldset>
	<form id="fileupload" enctype="multipart/form-data" action="<?php echo $TARGET; ?>" method="post">
        <?php $this->outputCsrfField() ?>
        <div  class="upload_field">
            <div>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $MAX_SIZE; ?>" />
                <label class="none" for="<?php echo $FILE.'1';?>"><?php p_('Select file');?></label>
                <input type="file" name="<?php echo $FILE.'1';?>[]" id="<?php echo $FILE; ?>" />
            </div>
        </div>
        <div>
            <span><input type="submit" name="submit" id="submit" value="<?php p_('Upload');?>" /></span>
            <span><input type="reset" name="clear" id="clear" value="<?php p_('Clear');?>" /></span>
        </div>
	</form>
    <div id="filedrop" class="dropzone"></div>
    <div style="margin-top: 20px">
        <?php if ($ENTRY_ATTACHMENTS): ?>
            <a name="entry-attachments" href="#" class="attachment-list-toggle"><?php p_("Entry attachments") ?></a>
            <ul class="entry-attachments attachment-list">
                <?php foreach ($ENTRY_ATTACHMENTS as $attachment): ?>
                    <li class="attachment" data-file="<?php echo $this->escape($attachment->getName())?>">
                        <?php echo $this->escape($attachment->getName()) ?>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
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
    </div>
</fieldset>
