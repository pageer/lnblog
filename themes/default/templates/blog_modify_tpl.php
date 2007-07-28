<?php
# Template: blog_modify_tpl.php
# This template is included by the <newblog.php> and <updateblog.php> pages.
# It contains the markup for setting the properties of a blog.
#
# Note that this also includes a small amount of code to scan the available 
# themes directories to get a list of themes.  This really should be moved 
# outside the template, but for the time being, just don't modify it.
?>
<h2><?php echo $UPDATE_TITLE; ?></h2>
<?php if (isset($UPDATE_MESSAGE)) { ?>
<p style="color: red"><?php echo $UPDATE_MESSAGE; ?></p>
<?php } ?>

<form id="addblog" method="post" action="<?php echo $POST_PAGE; ?>">
<?php if (isset($SHOW_BLOG_PATH)) { /* for new blogs */ ?>
<div>
<?php $msg = spf_("The path on the server where your blog will be created.  Enter an absolute path or a path relative to the server's document root.  To make the document root a blog, enter %s.  To make a subdomain a blog, enter the subdomain name.", ROOT_ID);?>
<label for="blogpath" title="<?php echo $msg;?>"><?php p_("Blog path"); ?></label>
<input id="blogpath" name="blogpath"  title="<?php echo $msg;?>" value="<?php echo $BLOG_PATH_REL; ?>" />
</div>
<?php } ?>
<?php if (isset($BLOG_OWNER)) { 
	$msg = _("The username of whoever will own this blog.");?>
<div>
<label for="owner" title="<?php echo $msg;?>"><?php p_("Blog owner"); ?></label>
<input id="owner" name="owner"  title="<?php echo $msg;?>" value="<?php echo $BLOG_OWNER; ?>" />
</div>
<?php } ?>
<div>
<?php $msg = _("A list of other usernames who can create posts on this blog.  Multiple usernames are separated by commas.");?>
<label for="writelist" title="<?php echo $msg;?>"><?php p_("Additional allowed writers"); ?></label>
<input id="writelist" name="writelist" title="<?php echo $msg;?>" value="<?php echo $BLOG_WRITERS; ?>" />
</div>
<div>
<?php $msg = _("The name of this blog.  This will be displayed as the blog title.");?>
<label for="blogname" title="<?php echo $msg;?>"><?php p_("Blog name"); ?></label>
<input id="blogname" name="blogname" title="<?php echo $msg;?>" value="<?php echo $BLOG_NAME; ?>" />
</div>
<div>
<?php $msg = _("A short description of the blog.");?>
<label for="desc" title="<?php echo $msg;?>"><?php p_("Description"); ?></label>
<input id="desc" name="desc" title="<?php echo $msg;?>" value="<?php echo $BLOG_DESC; ?>" />
</div>
<?php if (0) { ?>
<div>
<label for="image"><?php p_("Blog icon"); ?></label>
<input id="image" name="image" value="<?php echo $BLOG_IMAGE; ?>" />
</div>
<?php  } ?>
<div>
<?php $msg = _("The theme that determines how your pages look.  You can change this at any time.  You can also do per-blog modifications of the templates and style sheets to customize your blog.");?>
<label for="theme" title="<?php echo $msg;?>"><?php p_("Theme"); ?></label>
<select id="theme" name="theme" title="<?php echo $msg;?>">
<?php 
global $SYSTEM;
$dir = $SYSTEM->getThemeList();
foreach ($dir as $theme) { ?>
<option value="<?php echo $theme; ?>"<?php if (isset($BLOG_THEME) && $theme == $BLOG_THEME) { ?> selected="selected"<?php } ?>>
<?php echo $theme; ?></option>
<?php } ?>
</select>
</div>
<div>
<?php $msg = _("Number of entries on the main blog page.");?>
<label for="maxent" title="<?php echo $msg;?>"><?php p_("Maximum number of entries on front page"); ?></label>
<input id="maxent" name="maxent" size="3" title="<?php echo $msg;?>" value="<?php echo $BLOG_MAX; ?>" />
</div>
<div>
<?php $msg = _("Number of entries to keep in the RSS feeds.");?>
<label for="maxrss" title="<?php echo $msg;?>"><?php p_("Maximum number of entries in RSS feeds"); ?></label>
<input id="maxrss" name="maxrss" size="3" title="<?php echo $msg;?>" value="<?php echo $BLOG_RSS_MAX; ?>" />
</div>
<div>
<label for="use_abstract"><?php p_("Show only summaries on front page");?></label>
<input id="use_abstract" name="use_abstract" type="checkbox" <?php if ($BLOG_FRONT_PAGE_ABSTRACT) echo 'checked="checked"';?> />
</div>
<fieldset>
<legend style="font-weight: bold"><?php p_("Default settings for blog entries");?></legend>
<div>
<?php $msg = _("Determines when Pingbacks should be sent: whenever an entry is modified, only when it is first posted, or never.  You can manually override this on the entry edit screen.");?>
<label for="pingback" title="<?php echo $msg;?>"><?php p_("Send Pingbacks when posting entries"); ?></label>
<select id="pingback" name="pingback" title="<?php echo $msg;?>">
<option value="all"<?php 
if ($BLOG_AUTO_PINGBACK == 'all') { echo ' selected="selected"'; } 
?>>Always</option>
<option value="new"<?php 
if ($BLOG_AUTO_PINGBACK == 'new') { echo ' selected="selected"'; } 
?>>New entries only</option>
<option value="none"<?php
if ($BLOG_AUTO_PINGBACK == 'none') { echo ' selected="selected"'; } 
?>>Never</option>
</select>
</div>
<?php if (0) { /* Not yet implemented.*/ ?>
<div>
<label for="replies"><?php p_("Display comments, TrackBacks, and Pingbacks in a single list"); ?></label>
<input id="replies" name="replies"  type="checkbox" <?php 
if ($BLOG_GATHER_REPLIES) { ?> checked="checked"<?php } ?> />
</div>
<?php } ?>
<div>
<label for="allow_enc"><?php p_("Allow enclosures in entries (for Podcasting)"); ?></label>
<input id="allow_enc" name="allow_enc" type="checkbox" <?php 
if ($BLOG_ALLOW_ENC) { ?> checked="checked"<?php } ?> />
</div>
<div>
<?php $msg = _("Sets the default markup used to post entries.  Can be raw HTML code, LBCode, (LnBlog's dialect of BBCode), or simple text with automatic line breaks and clickable URLs.");?>
<label for="blogmarkup" title="<?php echo $msg;?>"><?php p_("Default markup mode for entries and articles"); ?></label>
<select id="blogmarkup" name="blogmarkup" title="<?php echo $msg;?>">
<option value="<?php echo MARKUP_NONE;?>"<?php if ($BLOG_DEFAULT_MARKUP == MARKUP_NONE) { echo ' selected="selected"'; } ?>><?php p_("Auto-markup");?></option>
<option value="<?php echo MARKUP_BBCODE;?>"<?php if ($BLOG_DEFAULT_MARKUP == MARKUP_BBCODE) { echo ' selected="selected"'; } ?>><?php p_("LBCode");?></option>
<option value="<?php echo MARKUP_HTML;?>"<?php if ($BLOG_DEFAULT_MARKUP == MARKUP_HTML) { echo ' selected="selected"'; } ?>><?php p_("HTML");?></option>
</select>
</div>
</fieldset>
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

