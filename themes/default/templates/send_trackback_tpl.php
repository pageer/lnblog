<h2><?php p_('TrackBack Entry');?></h2>
<?php if (isset($GET_TB_URL)) { ?>
<fieldset>
<form method="post" action="<?php echo $TARGET_PAGE; ?>">
<label for="<?php echo $GET_TB_URL; ?>"><?php p_('Enter TrackBack URL');?></label>
<input id="<?php echo $GET_TB_URL; ?>" name="<?php echo $GET_TB_URL; ?>" type="text" />
<input id="send_ping" name="send_ping" type="hidden" value="yes" />
<input type="submit" value="<?php p_('Send');?>" />
</form>
</fieldset>
<?php } else { ?>
<fieldset>
<h4><?php p_('Confirm Ping Content');?></h4>
<form method="post" id="tbconfirm" action="<?php echo $TARGET_PAGE; ?>">
<div>
<label for="url"><?php p_('Post <abbr title="Uniform Resource Locator">URL</abbr>');?></label>
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
<label for="excerpt"><?php p_('Post Excerpt');?></label>
<textarea id="excerpt" name="excerpt" class="textbox"><?php echo $TB_EXCERPT; ?></textarea>
</div>
<div class=>
<input type="submit" value="<?php p_('Send');?>" />
</div>
</form>
</fieldset>
<?php } ?>
