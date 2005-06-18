<h2>TrackBack Entry</h2>
<?php if (isset($GET_TB_URL)) { ?>
<fieldset>
<form method="post" action="<?php echo $TARGET_PAGE; ?>">
<label for="<?php echo $GET_TB_URL; ?>">Enter TrackBack URL</label>
<input id="<?php echo $GET_TB_URL; ?>" name="<?php echo $GET_TB_URL; ?>" type="text" />
<input id="send_ping" name="send_ping" type="hidden" value="yes" />
<input type="submit" value="Send" />
</form>
</fieldset>
<?php } else { ?>
<fieldset>
<h4>Confirm Ping Content</h4>
<form method="post" id="tbconfirm" action="<?php echo $TARGET_PAGE; ?>">
<div>
<label for="url">Post <abbr title="Uniform Resource Locator">URL</abbr></label>
<input id="url" name="url" value="<?php echo $TB_URL; ?>" />
</div>
<div>
<label for="title">Title</label>
<input id="title" name="title" value="<?php echo $TB_TITLE; ?>" />
</div>
<div>
<label for="blog_name">Blog name</label>
<input id="blog_name" name="blog_name" value="<?php echo $TB_BLOG; ?>" />
</div>
<div>
<label for="excerpt">Post Excerpt</label>
<textarea id="excerpt" name="excerpt" class="textbox"><?php echo $TB_EXCERPT; ?></textarea>
</div>
<div class=>
<input type="submit" value="Send" />
</div>
</form>
</fieldset>
<?php } ?>
