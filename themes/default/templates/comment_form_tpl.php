<div id="commentsubmit">
<?php if (isset($PARENT_TITLE)) { ?>
<h3><?php pf_("Add your comments on %s", $PARENT_TITLE); ?></h3>
<?php } else { ?>
<h3><?php p_("Add your comments"); ?></h3>
<?php } ?>
<p><?php p_("A comment body is required.  No HTML code allowed.  URLs starting with 
http:// or ftp:// will be automatically converted to hyperlinks."); ?>
<?php if(!COMMENT_EMAIL_VIEW_PUBLIC){ p_("Your e-mail address will not be displayed."); } ?></p>

<fieldset>
<form id="commentform" method="post" action="<?php echo $FORM_TARGET;?>">
<div>
<label class="basic_form_label" for="subject"><?php p_("Subject"); ?></label>
<input style="width: 70%" id="subject" name="subject" accesskey="s" type="text" />
</div>
<div>
<textarea id="data" name="data" accesskey="d" rows="10" cols="20"></textarea>
</div>
<div>
<label style="width: 40%" for="username"><?php p_("Name"); ?></label>
<input id="username" name="username" accesskey="n" type="text" <?php 
if (POST("username")) {
	echo 'value="'.POST("username").'" '; 
} elseif (COOKIE("username")) {
	echo 'value="'.COOKIE("username").'" '; 
}
?>/>
</div>
<div>
<label style="width: 40%" for="url"><?php p_("Homepage"); ?></label>
<input id="url" name="url" accesskey="h" type="text" <?php 
if (POST("url")) {
	echo 'value="'.POST("url").'" '; 
} elseif (COOKIE("url")) {
	echo 'value="'.COOKIE("url").'" '; 
}
?>/>
</div>
<div>
<label style="width: 40%" for="e-mail"><?php p_("E-Mail"); ?></label>
<input id="e-mail" name="e-mail" accesskey="e" type="text" <?php 
if (POST("e-mail")) {
	echo 'value="'.POST("e-mail").'" '; 
} elseif (COOKIE("e-mail")) {
	echo 'value="'.COOKIE("e-mail").'" '; 
}
?>/>
</div>
<div>
<label for="remember"><?php p_("Remember me"); ?></label>
<input id="remember" name="remember" type="checkbox" checked="checked" />
</div>
<div>
<span class="basic_form_submit"><input name="submit" id="submit" type="submit" value="<?php p_("Submit"); ?>" /></span>
<span class="basic_form_clear"><input name="clear" id="clear" type="reset" value="<?php p_("Clear"); ?>" /></span>
</div>
</form>
</fieldset>
</div>
