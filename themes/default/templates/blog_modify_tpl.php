<?php
# Template: blog_modify_tpl.php
# This template is included by the <newblog.php> and <updateblog.php> pages.
# It contains the markup for setting the properties of a blog.
#
# Note that this also includes a small amount of code to scan the available 
# themes directories to get a list of themes.  This really should be moved 
# outside the template, but for the time being, just don't modify it.
?>
<script type="application/javascript">
$(document).ready(function () {
    var default_path = '<?php echo $DEFAULT_PATH?>';
    var default_url = '<?php echo $DEFAULT_URL?>';
    var path_sep = '<?php echo $PATH_SEP?>';

    $('#blogid').on('change', function () {
        var path = default_path + path_sep + $(this).val().replace('/', path_sep) + path_sep;
        $('#blogpath').val(path);
        $('#blogurl').val(default_url + $(this).val() + '/');
    });
});
</script>
<h2><?php echo $UPDATE_TITLE; ?></h2>
<?php if (isset($UPDATE_MESSAGE)): ?>
    <p style="color: red"><?php echo $UPDATE_MESSAGE; ?></p>
<?php endif ?>

<form id="addblog" method="post" action="<?php echo $POST_PAGE; ?>">
    <?php $this->outputCsrfField() ?>
    <?php if (isset($SHOW_BLOG_PATH)): /* for new blogs */ ?>
        <div>
        <?php $msg = _("The unique internal identifier for the blog.");?>
            <label for="blogid" title="<?php echo $msg?>"><?php p_("Blog ID") ?></label>
            <input id="blogid" name="blogid"  title="<?php echo $msg?>" value="<?php echo $BLOG_ID?>" />
        </div>
        <div>
        <?php $msg = _("The absolute path on the server where your blog will be created.");?>
            <label for="blogpath" title="<?php echo $msg?>"><?php p_("Blog path") ?></label>
            <input id="blogpath" name="blogpath"  title="<?php echo $msg?>" value="<?php echo $BLOG_PATH?>" />
        </div>
        <div>
        <?php $msg = _("The URL of the blog directory (this may depend on your server configuration).");?>
            <label for="blogurl" title="<?php echo $msg?>"><?php p_("Blog URL") ?></label>
            <input id="blogurl" name="blogurl"  title="<?php echo $msg?>" value="<?php echo $BLOG_URL?>" />
        </div>
    <?php endif ?>
    <?php if (isset($BLOG_OWNER)):?>
        <?php $msg = _("The username of whoever will own this blog.");?>
        <div>
            <label for="owner" title="<?php echo $msg;?>"><?php p_("Blog owner"); ?></label>
            <input id="owner" name="owner"  title="<?php echo $msg;?>" value="<?php echo $BLOG_OWNER; ?>" />
        </div>
    <?php endif ?>
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
    <div>
        <?php $msg = _("The theme that determines how your pages look.  You can change this at any time.  You can also do per-blog modifications of the templates and style sheets to customize your blog.");?>
        <label for="theme" title="<?php echo $msg;?>"><?php p_("Theme"); ?></label>
        <select id="theme" name="theme" title="<?php echo $msg;?>">
        <?php $dir = System::instance()->getThemeList() ?>
        <?php foreach ($dir as $theme): ?>
            <?php $selected = (isset($BLOG_THEME) && $theme == $BLOG_THEME) ? 'selected="selected"' : ''; ?>
            <option value="<?php echo $theme; ?>" <?php echo $selected ?>>
                <?php echo $theme ?>
            </option>
        <?php endforeach ?>
        </select>
    </div>
    <div>
        <?php $msg = _("Number of entries on the main blog page.");?>
        <label for="maxent" title="<?php echo $msg;?>"><?php p_("Maximum number of entries on front page"); ?></label>
        <input id="maxent" name="maxent" size="3" title="<?php echo $msg?>" value="<?php echo $BLOG_MAX?>" />
    </div>
    <div>
        <?php $msg = _("Number of entries to keep in the RSS feeds.");?>
        <label for="maxrss" title="<?php echo $msg;?>"><?php p_("Maximum number of entries in RSS feeds"); ?></label>
        <input id="maxrss" name="maxrss" size="3" title="<?php echo $msg?>" value="<?php echo $BLOG_RSS_MAX?>" />
    </div>
    <div>
        <label for="use_abstract"><?php p_("Show only summaries on front page");?></label>
        <?php $checked = $BLOG_FRONT_PAGE_ABSTRACT ? 'checked="checked"' : ''; ?>
        <input id="use_abstract" name="use_abstract" type="checkbox" <?php echo $checked?> />
    </div>
    <div>
        <label for="main_entry"><?php p_("Article to show as front page");?></label>
        <input id="main_entry" name="main_entry" type="input" size="20" value="<?php echo $BLOG_MAIN_ENTRY;?>" />
    </div>
    <fieldset>
        <legend style="font-weight: bold"><?php p_("Default settings for blog entries");?></legend>
        <div>
            <?php $msg = _("Determines when Pingbacks should be sent by default for new posts.  You can manually override this on the entry edit screen.");?>
            <?php $pingback_checked = $BLOG_AUTO_PINGBACK ? 'checked="checked"' : ''; ?>
            <label for="pingback" title="<?php echo $msg;?>"><?php p_("Send Pingbacks by default when posting entries"); ?></label>
            <input type="checkbox" id="pingback" name="pingback" title="<?php echo $msg?>" <?php echo $pingback_checked?> />
        </div>
        <div>
            <label for="allow_enc"><?php p_("Allow enclosures in entries (for Podcasting)"); ?></label>
            <?php $checked = $BLOG_ALLOW_ENC ? 'checked="checked"' : ''; ?>
            <input id="allow_enc" name="allow_enc" type="checkbox" <?php echo $checked ?> ?>
        </div>
        <div>
            <?php $title = _("Set the markup type for this entry.  Auto-markup is plain text with clickable URLs, LBcode is a dialect of the BBcode markup popular on web forums, and HTML is raw HTML code.");?>
            <label for="input_mode" title="<?php echo $title?>"><?php p_("Markup type")?></label>
            <select id="input_mode" name="input_mode" title="<?php echo $title?>">
                <?php foreach (TextProcessor::getAvailableFilters() as $filter): ?>
                    <?php $selected = ($BLOG_DEFAULT_MARKUP == $filter->filter_id)  ?' selected="selected"' : ''; ?>
                    <option value="<?php echo $filter->filter_id?>" <?php echo $selected?>>
                        <?php p_($filter->filter_name)?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </fieldset>
    <div style="text-align: center">
        <?php if ( defined("INSTALL_ROOT_URL") && defined("BLOG_ROOT") && 
                   is_dir(BLOG_ROOT) && BLOG_ROOT != INSTALL_ROOT) { 
            $blog = NewBlog(); ?>
        <a href="<?php echo $blog->getURL();?>?action=blogpaths">Edit paths for blog</a>
        <?php } ?>
    </div>
    <div>
        <span class="basic_form_submit"><input name="submit" id="submit" type="submit" value="<?php p_("Submit"); ?>" /></span>
        <span class="basic_form_clear"><input name="clear" id="clear" type="reset" value="<?php p_("Clear"); ?>" /></span>
    </div>
</form>

