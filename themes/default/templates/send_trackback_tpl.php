<h2><?php p_('TrackBack Entry');?></h2>
<fieldset>
<h4><?php p_('Trackback Ping Content');?></h4>
<?php if ( isset($ERROR_MESSAGE) ) { ?>
<p><?php echo $ERROR_MESSAGE;?></p>
<?php } ?>
<form method="post" id="tbconfirm" action="<?php echo make_uri(false,false,false); ?>">
<div>
<label for="target_url"><?php p_('Target Trackback <abbr title="Uniform Resource Locator">URL</abbr>');?></label>
<input id="target_url" name="target_url" value="<?php echo $TARGET_URL; ?>" />
</div>
<div>
<label for="url"><?php p_('Entry <abbr title="Uniform Resource Locator">URL</abbr>');?></label>
<input id="url" name="url" value="<?php echo $TB_URL; ?>" />
</div>
<div>
<label for="title"><?php p_('Title');?></label>
<input id="title" name="title" value="<?php echo $TB_TITLE; ?>" />
</div>
<div>
<label for="blog_name"><?php p_('Blog name');?></label>
<input id="blog_name" name="blog_name" value="<?php echo $TB_BLOG; ?>" />
</div>
<div>
<label for="excerpt"><?php p_('Entry Excerpt');?></label>
<textarea id="excerpt" name="excerpt" class="textbox" cols="40" rows="10">
<?php echo $TB_EXCERPT; ?>
</textarea>
</div>
<div>
<span class="basic_form_submit"><input type="submit" value="<?php p_('Send');?>" /></span>
<span class="basic_form_reset"><input type="reset" value="<?php p_('Reset');?>" /></span>
</div>
</form>
</fieldset>
