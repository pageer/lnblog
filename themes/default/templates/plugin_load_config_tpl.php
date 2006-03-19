<h2><?php p_("Plugin Loading Configuration"); ?></h2>
<?php if (isset($UPDATE_MESSAGE)) { ?>
<p><strong><?php echo $UPDATE_MESSAGE; ?></strong></p>
<?php } ?>
<form method="post" action="<?php echo current_file();?>">
<table>
<tr><th>Plugin File</th><th>Load Order</th><th>Enabled</th></tr>
<?php foreach ($PLUGIN_LIST as $file=>$val) { ?>
<tr>
<td><?php echo $val["file"];?></td>
<td><input type="text" size="11" name="<?php echo $file."_ord";
	?>" id="<?php echo $file."_ord";?>" value="<?php echo $val["order"];?>" /></td>
<td><input type="checkbox" name="<?php echo $file."_en";
	?>" id="<?php echo $file."_en";?>" <?php 
	if ($val["enabled"]) { ?>checked="checked"<?php } ?> /></td>
</tr>
<?php } ?>
</table>
<div>
<input type="submit" value="<?php p_("Submit");?>" />
<input type="reset" value="<?php p_("Reset");?>" />
</div>
</form>
