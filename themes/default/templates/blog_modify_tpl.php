<h2><?php echo $UPDATE_TITLE; ?></h2>
<?php if (isset($UPDATE_MESSAGE)) { ?>
<p style="color: red"><?php echo $UPDATE_MESSAGE; ?></p>
<?php } ?>

<form id="addblog" method="post" action="<?php echo $POST_PAGE; ?>">
<?php if (isset($SHOW_BLOG_PATH)) { /* for new blogs */ ?>
<div>
<label for="blogpath"><?php p_("Blog path"); ?></label>
<input id="blogpath" name="blogpath" value="<?php echo $BLOG_PATH_REL; ?>" />
</div>
<?php } ?>
<?php if (isset($BLOG_OWNER)) { ?>
<div>
<label for="owner"><?php p_("Blog owner"); ?></label>
<input id="owner" name="owner" value="<?php echo $BLOG_OWNER; ?>" />
</div>
<?php } ?>
<div>
<label for="writelist"><?php p_("Additional allowed writers"); ?></label>
<input id="writelist" name="writelist" value="<?php echo $BLOG_WRITERS; ?>" />
</div>
<div>
<label for="blogname"><?php p_("Blog name"); ?></label>
<input id="blogname" name="blogname" value="<?php echo $BLOG_NAME; ?>" />
</div>
<div>
<label for="desc"><?php p_("Description"); ?></label>
<input id="desc" name="desc" value="<?php echo $BLOG_DESC; ?>" />
</div>
<?php if (0) { ?>
<div>
<label for="image"><?php p_("Blog icon"); ?></label>
<input id="image" name="image" value="<?php echo $BLOG_IMAGE; ?>" />
</div>
<?php  } ?>
<div>
<label for="theme"><?php p_("Theme"); ?></label>
<select id="theme" name="theme">
<?php 
$dir = scan_directory(INSTALL_ROOT.PATH_DELIM."themes", true);
if (is_dir(USER_DATA_PATH.PATH_DELIM."themes")) {
	$user_dir = scan_directory(USER_DATA_PATH.PATH_DELIM."themes", true);
	if ($user_dir) $dir = array_merge($dir, $user_dir);
}
if (defined("BLOG_ROOT") && is_dir(BLOG_ROOT.PATH_DELIM."themes")) {
	$blog_dir = scan_directory(BLOG_ROOT.PATH_DELIM."themes", true);
	if ($blog_dir) $dir = array_merge($dir, $blog_dir);
}
$dir = array_unique($dir);
sort($dir);
foreach ($dir as $theme) { ?>
<option value="<?php echo $theme; ?>"<?php if (isset($BLOG_THEME) && $theme == $BLOG_THEME) { ?> selected="selected"<?php } ?>>
<?php echo $theme; ?></option>
<?php } ?>
</select>
</div>
<div>
<label for="maxent"><?php p_("Maximum number of entries on front page"); ?></label>
<input id="maxent" name="maxent" size="3" value="<?php echo $BLOG_MAX; ?>" />
</div>
<div>
<label for="maxrss"><?php p_("Maximum number of entries in RSS feeds"); ?></label>
<input id="maxrss" name="maxrss" size="3" value="<?php echo $BLOG_RSS_MAX; ?>" />
</div>
<fieldset>
<div>
<label for="pingback"><?php p_("Send Pingbacks when posting entries"); ?></label>
<input id="pingback" name="pingback"  type="checkbox" <?php 
if ($BLOG_AUTO_PINGBACK) { ?>checked="checked"<?php } ?> />
</div>
<?php if (0) { /* Not yet implemented.*/ ?>
<div>
<label for="replies"><?php p_("Display comments, TrackBacks, and Pingbacks in a single list"); ?></label>
<input id="replies" name="replies"  type="checkbox" <?php 
if ($BLOG_GATHER_REPLIES) { ?>checked="checked"<?php } ?> />
</div>
<?php } ?>
<div>
<label for="allow_enc"><?php p_("Allow enclosures in entries (for Podcasting)"); ?></label>
<input id="allow_enc" name="allow_enc" type="checkbox" <?php 
if ($BLOG_ALLOW_ENC) { ?>checked="checked"<?php } ?> />
</div>
<div>
<label for="blogmarkup"><?php p_("Default markup mode for entries and articles"); ?></label>
<select id="blogmarkup" name="blogmarkup">
<option value="<?php echo MARKUP_NONE;?>"<?php if ($BLOG_DEFAULT_MARKUP == MARKUP_NONE) { echo ' selected="selected"'; } ?>><?php p_("Auto-markup");?></option>
<option value="<?php echo MARKUP_BBCODE;?>"<?php if ($BLOG_DEFAULT_MARKUP == MARKUP_BBCODE) { echo ' selected="selected"'; } ?>><?php p_("LBCode");?></option>
<option value="<?php echo MARKUP_HTML;?>"<?php if ($BLOG_DEFAULT_MARKUP == MARKUP_HTML) { echo ' selected="selected"'; } ?>><?php p_("HTML");?></option>
</select>
</div>
<div style="text-align: center">
<?php if ( defined("INSTALL_ROOT_URL") && defined("BLOG_ROOT") && 
           is_dir(BLOG_ROOT) && BLOG_ROOT != INSTALL_ROOT) { 
	$blog = NewBlog(); ?>
<a href="<?php echo INSTALL_ROOT_URL;?>pages/blogpaths.php?blog=<?php echo $blog->blogid;?>">Edit paths for blog</a>
<?php } ?>
</div>
<div>
<span class="basic_form_submit"><input name="submit" id="submit" type="submit" value="<?php p_("Submit"); ?>" /></span>
<span class="basic_form_clear"><input name="clear" id="clear" type="reset" value="<?php p_("Clear"); ?>" /></span>
</div>
</form>

