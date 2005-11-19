<h2><?php echo $UPDATE_TITLE; ?></h2>
<?php if (isset($UPDATE_MESSAGE)) { ?>
<p style="color: red"><?php echo $UPDATE_MESSAGE; ?></p>
<?php } ?>
<fieldset>
<form id="addblog" method="post" action="<?php echo $POST_PAGE; ?>">
<?php if (isset($BLOG_PATH_ID)) { # for new blogs ?>
<div>
<label for="<?php echo $BLOG_PATH_ID; ?>"><?php p_("Blog path"); ?></label>
<input id="<?php echo $BLOG_PATH_ID; ?>" name="<?php echo $BLOG_PATH_ID; ?>" value="<?php echo $BLOG_PATH_REL; ?>" />
</div>
<?php } ?>
<?php if (isset($BLOG_OWNER_ID)) { ?>
<div>
<label for="<?php echo $BLOG_OWNER_ID; ?>"><?php p_("Blog owner"); ?></label>
<input id="<?php echo $BLOG_OWNER_ID; ?>" name="<?php echo $BLOG_OWNER_ID; ?>" value="<?php echo $BLOG_OWNER; ?>" />
</div>
<?php } ?>
<div>
<label for="<?php echo $BLOG_WRITERS_ID; ?>"><?php p_("Additional allowed writers"); ?></label>
<input id="<?php echo $BLOG_WRITERS_ID; ?>" name="<?php echo $BLOG_WRITERS_ID; ?>" value="<?php echo $BLOG_WRITERS; ?>" />
</div>
<div>
<label for="<?php echo $BLOG_NAME_ID; ?>"><?php p_("Blog name"); ?></label>
<input id="<?php echo $BLOG_NAME_ID; ?>" name="<?php echo $BLOG_NAME_ID; ?>" value="<?php echo $BLOG_NAME; ?>" />
</div>
<div>
<label for="<?php echo $BLOG_DESC_ID; ?>"><?php p_("Description"); ?></label>
<input id="<?php echo $BLOG_DESC_ID; ?>" name="<?php echo $BLOG_DESC_ID; ?>" value="<?php echo $BLOG_DESC; ?>" />
</div>
<?php if (0) { ?>
<div>
<label for="<?php echo $BLOG_IMAGE_ID; ?>"><?php p_("Blog icon"); ?></label>
<input id="<?php echo $BLOG_IMAGE_ID; ?>" name="<?php echo $BLOG_IMAGE_ID; ?>" value="<?php echo $BLOG_IMAGE; ?>" />
</div>
<?php  } ?>
<div>
<label for="<?php echo $BLOG_THEME_ID; ?>"><?php p_("Theme"); ?></label>
<select id="<?php echo $BLOG_THEME_ID; ?>" name="<?php echo $BLOG_THEME_ID; ?>">
<?php 
$dir = scan_directory(INSTALL_ROOT.PATH_DELIM."themes", true);
sort($dir);
foreach ($dir as $theme) { ?>
<option value="<?php echo $theme; ?>"<?php if (isset($BLOG_THEME) && $theme == $BLOG_THEME) { ?> selected="selected"<?php } ?>>
<?php echo $theme; ?></option>
<?php } ?>
</select>
</div>
<div>
<label for="<?php echo $BLOG_MAX_ID; ?>"><?php p_("Maximum number of entries"); ?></label>
<input id="<?php echo $BLOG_MAX_ID; ?>" name="<?php echo $BLOG_MAX_ID; ?>" value="<?php echo $BLOG_MAX; ?>" />
</div>
<div>
<label for="<?php echo $BLOG_RSS_MAX_ID; ?>"><?php p_("Maximum number of entries in RSS feeds"); ?></label>
<input id="<?php echo $BLOG_RSS_MAX_ID; ?>" name="<?php echo $BLOG_RSS_MAX_ID; ?>" value="<?php echo $BLOG_RSS_MAX; ?>" />
</div>
<div>
<span class="basic_form_submit"><input name="<?php echo $SUBMIT_ID; ?>" id="<?php echo $SUBMIT_ID; ?>" type="submit" value="<?php p_("Submit"); ?>" /></span>
<span class="basic_form_clear"><input name="clear" id="clear" type="reset" value="<?php p_("Clear"); ?>" /></span>
</div>
</form>
</fieldset>
