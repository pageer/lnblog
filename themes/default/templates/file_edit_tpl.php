<h1><?php echo $PAGE_TITLE;?></h1>
<?php if (isset($EDIT_ERROR)) { ?>
<h3><?php echo $EDIT_ERROR; ?></h3>
<p><?php echo $ERROR_MESSAGE; ?></p>
<?php } 
if (isset($FORM_MESSAGE)) { ?>
<p><?php echo $FORM_MESSAGE;?></p>
<?php } ?>
<?php if (isset($FILE_URL)) { ?>
<p><strong><?php p_('File URL');?></strong>: <?php echo $FILE_URL;?></p>
<?php }
if (isset($FILE_PATH)) { ?>
<p><strong><?php p_('Local path');?></strong>: <?php echo $FILE_PATH;?></p>
<?php }
if (isset($FILE_SIZE)) { ?>
<p><strong><?php p_('File size');?></strong>: <?php echo $FILE_SIZE;?></p>
<?php } ?>
<?php if (isset($SHOW_LINK_EDITOR)) { ?>
<p><?php p_('<strong>Note:</strong> You must have Javascript enabled to use the edit boxes.');?></p>
<fieldset class="formbox">
<div>
<label for="linktext"><?php p_('Regular text');?></label>
<input type="text" id="linktext" name="linktext" />
</div>
<div>
<label for="linktitle"><?php p_('Hover text ("tooltip" text)');?></label>
<input type="text" id="linktitle" name="linktitle" />
</div>
<div>
<label for="linktarget"><?php p_('Link <abbr title="Unirofm Resource Locator">URL</abbr>');?></label>
<input type="text" id="linktarget" name="linktarget" />
</div>
<div style="text-align: center">
<button id="addlink" onclick="addLink();" /><?php p_('Add link');?></button>
<button id="clear" onclick="clearBoxes();" /><?php p_('Clear');?></button>
</div>
<?php } ?>
<fieldset>
<form method="post" action="<?php echo $FORM_ACTION; ?>">
<?php $this->outputCsrfField() ?>
<textarea id="output" name="output" rows="10"><?php if (isset($FILE_TEXT)) echo $FILE_TEXT; ?></textarea>
<div><input type="submit" value="<?php p_('Save File');?>" /><input type="reset" value="<?php p_('Reset Changes');?>" />
<input type="hidden" name="file" value="<?php echo $FILE;?>" /></div>
</form>
</fieldset>
