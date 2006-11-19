<form method="post" action="<?php echo $POST_PAGE;?>">
<fieldset>
<legend><?php p_("Page Banner");?></legend>
<legend><?php P_("Background");?></legend>
	<fieldset>
	<legend><?php p_("Banner background");?></legend>
	<label for="ban_bg"><?php p_("Background color");?></label>
	<input id="ban_bg" name="ban_bg" type="text" value="<?php echo $BAN_BG;?>" />
	<label for="ban_bgurl"><?php p_("Background image URL");?></label>
	<input id="ban_bgurl" name="ban_bgurl" type="text" value="<?php echo $BAN_BGURL;?>" />
	</fieldset>
	<fieldset>
	<legend><?php p_("Banner size");?></legend>
	<p><?php p_("If you set a background image, these should normally be set to its dimensions.");?></p>
	<label for="ban_maxwidth"><?php p_("Maximum width");?></label>
	<input id="ban_maxwidth" name="ban_maxwidth" type="text" value="<?php echo $BAN_MAXWIDTH;?>" />
	<label for="ban_maxheight"><?php p_("Maximum height");?></label>
	<input id="ban_maxheight" name="ban_maxheight" type="text" value="<?php echo $BAN_MAXHEIGHT;?>" />
	</fieldset>
	<fieldset>
	<legend><?php p_("Title Font");?></legend>
	<label for="ban_tfont"><?php p_("Font");?></label>
	<input id="ban_tfont" name="ban_tfont" type="text" value="<?php echo $BAN_TFONT;?>" />
	<label for="ban_tfontfam"><?php p_("Font family");?></label>
	<input id="ban_tfontfam" name="ban_tfontfam" type="text" value="<?php echo $BAN_TFONTFAM;?>" />
	<label for="ban_tcolor"><?php p_("Text color");?></label>
	<input id="ban_tcolor" name="ban_tcolor" type="text" value="<?php echo $BAN_TCOLOR;?>" />
	<label for="ban_talign"><?php p_("Text alignment");?></label>
	<select id="ban_talign" name="ban_talign">
	<option value="left"<?php if ($BAN_TALIGN == 'left') echo ' selected="selected"';?>><?php p_("Left");?></option>
	<option value="center"<?php if ($BAN_TALIGN == 'center') echo ' selected="selected"';?>><?php p_("Center");?></option>
	<option value="right"<?php if ($BAN_TALIGN == 'right') echo ' selected="selected"';?>><?php p_("Right");?></option>
	</select>
	</fieldset>
	<fieldset>
	<legend><?php p_("Description Font");?></legend>
	<label for="ban_dfont"><?php p_("Font");?></label>
	<input id="ban_dfont" name="ban_dfont" type="text" value="<?php echo $BAN_DFONT;?>" />
	<label for="ban_dfontfam"><?php p_("Font family");?></label>
	<input id="ban_dfontfam" name="ban_dfontfam" type="text" value="<?php echo $BAN_DFONTFAM;?>" />
	<label for="ban_dcolor"><?php p_("Text color");?></label>
	<input id="ban_dcolor" name="ban_dcolor" type="text" value="<?php echo $BAN_DCOLOR;?>" />
	<select id="ban_dalign" name="ban_talign">
	<option value="left""ban_linkstyle" ><?php p_("Left");?></option>
	<option value="center"<?php if ($BAN_DALIGN == 'center') echo ' selected="selected"';?>><?php p_("Center");?></option>
	<option value="right"<?php if ($BAN_DALIGN == 'right') echo ' selected="selected"';?>><?php p_("Right");?></option>
	</select>
	</fieldset>
<label for="ban_linkstyle"><?php p_("Show underlines for hyperlinks");?></label>
<select id="ban_linkstyle" name="ban_linkstyle">
<option value="always"<?php if ($BAN_LINKSTYLE == 'always') echo ' selected="selected"';?>><?php p_("Always");?></option>
<option value="never"<?php if ($BAN_LINKSTYLE == 'never') echo ' selected="selected"';?>><?php p_("Never");?></option>
<option value="hover"<?php if ($BAN_LINKSTYLE == 'hover') echo ' selected="selected"';?>><?php p_("On hover");?></option>
</select>
</fieldset>
<fieldset>
<legend><?php p_("Sidebar");?></legend>
<label for="sb_position"><?php p_("Sidebar position");?></label>
<select id="sb_position" name="sb_position">
<option value="right" <?php if ($SB_POS == 'right') echo 'selected="selected"';?>><?php p_("Right");?></option>
<option value="left" <?php if ($SB_POS == 'left') echo 'selected="selected"';?>><?php p_("Left");?></option>
</select>
	<fieldset>
	<legend><?php p_("Section Heading");?></legend>
	<label = for"sb_headfont"><?php p_("Font");?></label>
	<input id="sb_headfont" name="sb_headfont" type="text" value="<?php echo $SB_HEADFONT;?>" />
	<label = for"sb_headff"><?php p_("Font family");?></label>
	<input id="sb_headff" name="sb_headff" type="text" value="<?php echo $SB_HEADFGF;?>" />
	<label = for"sb_headbg"><?php p_("Background color");?></label>
	<input id="sb_headg" name="sb_headbg" type="text" value="<?php echo $SB_HEADBG;?>" />
	<label = for"sb_headfg"><?php p_("Font color");?></label>
	<input id="sb_headfg" name="sb_headfg" type="text" value="<?php echo $SB_HEADFG;?>" />
	<label for="sb_linkstyle"><?php p_("Show underlines for hyperlinks");?></label>
	<select id="sb_linkstyle" name="sb_linkstyle">
	<option value="always"<?php if ($BAN_LINKSTYLE == 'always') echo ' selected="selected"';?>><?php p_("Always");?></option>
	<option value="never"<?php if ($BAN_LINKSTYLE == 'never') echo ' selected="selected"';?>><?php p_("Never");?></option>
	<option value="hover"<?php if ($BAN_LINKSTYLE == 'hover') echo ' selected="selected"';?>><?php p_("On hover");?></option>
	</select>
	
	<label = for"sb_bodybg"><?php p_("Section body background color");?></label>
	<input id="sb_bodyg" name="sb_bodybg" type="text" value="<?php echo $SB_HEADBG;?>" />
	<label = for"sb_bodyfg"><?php p_("Section body font color");?></label>
	<input id="sb_bodyfg" name="sb_bodybg" type="text" value="<?php echo $SB_HEADFG;?>" />
	</fieldset>
</fieldset>
</form>
