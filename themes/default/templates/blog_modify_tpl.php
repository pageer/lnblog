<h2><?php echo $UPDATE_TITLE; ?></h2>
<?php if (isset($UPDATE_MESSAGE)) { ?>
<p><?php echo $UPDATE_MESSAGE; ?></p>
<?php } ?>
<fieldset>
<form id="addblog" method="post" action="<?php echo $POST_PAGE; ?>">
<?php if (isset($BLOG_PATH_ID)) { ?>
<div>
<span class="basic_form_label"><label for="<?php echo $BLOG_PATH_ID; ?>">Blog path</label></span>
<span class="basic_form_data"><input id="<?php echo $BLOG_PATH_ID; ?>" name="<?php echo $BLOG_PATH_ID; ?>" value="<?php echo $BLOG_PATH_REL; ?>" /></span>
</div>
<?php } ?>
<div>
<span class="basic_form_label"><label for="<?php echo $BLOG_NAME_ID; ?>">Blog name</label></span>
<span class="basic_form_data"><input id="<?php echo $BLOG_NAME_ID; ?>" name="<?php echo $BLOG_NAME_ID; ?>" value="<?php echo $BLOG_NAME; ?>" /></span>
</div>
<div>
<span class="basic_form_label"><label for="<?php echo $BLOG_DESC_ID; ?>">Description</label></span>
<span class="basic_form_data"><input id="<?php echo $BLOG_DESC_ID; ?>" name="<?php echo $BLOG_DESC_ID; ?>" value="<?php echo $BLOG_DESC; ?>" /></span>
</div>
<?php if (0) { ?>
<div>
<span class="basic_form_label"><label for="<?php echo $BLOG_IMAGE_ID; ?>">Blog icon</label></span>
<span class="basic_form_data"><input id="<?php echo $BLOG_IMAGE_ID; ?>" name="<?php echo $BLOG_IMAGE_ID; ?>" value="<?php echo $BLOG_IMAGE; ?>" /></span>
</div>
<?php  } ?>
<div>
<span class="basic_form_label"><label for="<?php echo $BLOG_THEME_ID; ?>">Theme</label></span>
<span class="basic_form_data"><select id="<?php echo $BLOG_THEME_ID; ?>" name="<?php echo $BLOG_THEME_ID; ?>">
<?php 
$dir = scan_directory(INSTALL_ROOT.PATH_DELIM."themes", true);
sort($dir);
foreach ($dir as $theme) { ?>
<option value="<?php echo $theme; ?>"<?php if (isset($BLOG_THEME) && $theme == $BLOG_THEME) { ?> selected="selected"<?php } ?>>
<?php echo $theme; ?></option>
<?php } ?>
</select></span>
</div>
<div>
<span class="basic_form_label"><label for="<?php echo $BLOG_MAX_ID; ?>">Maximum number of entries</label></span>
<span class="basic_form_data"><input id="<?php echo $BLOG_MAX_ID; ?>" name="<?php echo $BLOG_MAX_ID; ?>" value="<?php echo $BLOG_MAX; ?>" /></span>
</div>
<div>
<span class="basic_form_submit"><input name="<?php echo $SUBMIT_ID; ?>" id="<?php echo $SUBMIT_ID; ?>" type="submit" value="Submit" /></span>
<span class="basic_form_clear"><input name="clear" id="clear" type="reset" value="Clear" /></span>
</div>
</form>
</fieldset>
