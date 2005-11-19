<h2><?php p_('TrackBack Entry');?></h2>
<fieldset>
<?php if (isset($ERROR_MESSAGE)) { ?>
<h3><?php echo $ERROR_MESSAGE; ?></h3>
<?php } ?>
<form id="sendtb" method="post" action="<?php echo current_file(); ?>">
<div>
<label for="<?php echo $TB_URL_ID; ?>"><?php p_('Enter TrackBack URL');?></label>
<input id="<?php echo $TB_URL_ID; ?>" name="<?php echo $TB_URL_ID; ?>" type="text" value="<?php echo $TB_URL; ?>" />
</div>
<div>
<label for="excerpt"><?php p_('Excerpt');?></label>
<textarea id="<?php echo $TB_EXCERPT_ID; ?>" name="<?php echo $TB_EXCERPT_ID; ?>"><?php if (isset($TB_EXCERPT)) { echo $TB_EXCERPT; } ?></textarea>
<input type="hidden" id="send_ping" name="send_ping" value="yes" />
</div>
<div>
<input type="submit" value="<?php p_('Send');?>" />
</div>
</form>
</fieldset>
