<h2><?php p_("Plugin Loading Configuration"); ?></h2>
<?php if (isset($UPDATE_MESSAGE)) { ?>
<p><strong><?php echo $UPDATE_MESSAGE; ?></strong></p>
<?php } ?>
<div>
<label for="plugin_list"><?php p_("Available Plugins");?></label><br />
<textarea id="plugin_list" cols="40" rows="5"><?php echo $PLUGIN_LIST; ?></textarea>
</div>
<form method="post" action="<?php echo current_file();?>">
<div>
<label for="exclude_list"><?php p_("Disabled Plugins");?></label><br />
<textarea id="exclude_list" name="exclude_list" cols="40" rows="5"><?php echo $EXCLUDE_LIST; ?></textarea>
</div>
<div>
<label for="load_first"><?php p_("Load first");?></label><br />
<textarea id="load_first" name="load_first" cols="40" rows="5"><?php echo $LOAD_FIRST; ?></textarea>
</div>
<div>
<input type="submit" value="<?php p_("Submit");?>" />
<input type="reset" value="<?php p_("Reset");?>" />
</div>
</form>
