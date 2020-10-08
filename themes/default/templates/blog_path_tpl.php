<?php 
# Template: blog_path_tpl.php
# Contains the markup used to set the blog INSTALL_ROOT and INSTALL_ROOT_URL
# configuration constants.  This is included by the <blogpaths.php> page.
?>
<h2><?php p_("Paths and URLs");?></h2>
<p><?php p_("Use this page to adjust the path and URL for the LnBlog installation.  Use this is you want to make a blog use a different version of LnBlog, if you want to use a URL that does not map directly to the install path, or if you did the initial setup on a different server.");?></p>
<?php if (isset($UPDATE_MESSAGE)) { ?>
<p style="color: red"><?php echo $UPDATE_MESSAGE;?></p>
<?php } ?>
<form method="post" action="<?php echo $POST_PAGE;?>">
<?php $this->outputCsrfField() ?>
<?php if (0) { /*Comment out BLOG_ROOT_URL section. */ ?>
<div>
<label for="blogrooturl"><?php p_("Blog root URL"); ?></label>
<input id="blogrooturl" name="blogrooturl" size="30" value="<?php echo $BLOG_URL;?>" />
</div>
<?php } ?>
<div>
<label for="installroot"><?php p_("LnBlog installation root"); ?></label>
<input id="installroot" name="installroot" size="30" value="<?php echo $INST_ROOT;?>" />
</div>
<div>
<label for="installrooturl"><?php p_("LnBlog installation URL"); ?></label>
<input id="installrooturl" name="installrooturl" size="30" value="<?php echo $INST_URL;?>" />
</div>
<div>
<label for="blogurl"><?php p_("URL for this blog"); ?></label>
<input id="blogurl" name="blogurl" size="30" value="<?php echo $BLOG_URL;?>" />
</div>
<div>
<span class="basic_form_submit"><input name="submit" id="submit" type="submit" value="<?php p_("Submit"); ?>" /></span>
<span class="basic_form_clear"><input name="clear" id="clear" type="reset" value="<?php p_("Reset"); ?>" /></span>
</div>
</form>
