<div id="commentsubmit">
<h3>
<?php 
# Include a link to the parent entry if there are no comments on it.
if (! empty($PARENT_TITLE)) {
    pf_('Add your comments on <a href="%s">%s</a>', $PARENT_URL, $PARENT_TITLE);
} else { 
    p_("Add your comments");
} ?>
&nbsp;<a href="#postcommentform" name="postcommentform" class="anchor">#</a>
</h3>
<p>
<?php
# Set the message to display on the form.  If not given, use the default.
if (isset($COMMENT_FORM_MESSAGE)) {
    echo $COMMENT_FORM_MESSAGE;
} else {
    p_("A comment body is required.  No HTML code allowed.  URLs starting with http:// or ftp:// will be automatically converted to hyperlinks."); 
}?>
</p>
<?php
EventRegister::instance()->activateEventFull($tmp=false, "commentform", "BeforeForm");?>
<fieldset>
<form id="commentform" method="post" action="<?php echo $FORM_TARGET;?>" accept-charset="<?php echo DEFAULT_CHARSET;?>">
<?php $this->outputCsrfField() ?>
<?php EventRegister::instance()->activateEventFull($tmp=false, "commentform", "FormBegin");?>
<div>
<label class="basic_form_label" for="subject"><?php p_("Subject"); ?></label>
<input style="width: 70%" title="<?php p_("Subject");?>" id="subject" name="subject" accesskey="s" <?php
if (isset($COMMENT_SUBJECT)) echo "value=\"$COMMENT_SUBJECT\""; ?> type="text" />
</div>
<div>
<textarea title="<?php p_("Comment body");?>" id="data" name="data" accesskey="b" rows="10" cols="20"><?php
if (isset($COMMENT_DATA)) echo $COMMENT_DATA;
?></textarea>
</div>
<div>
<label style="width: 40%" for="username"><?php p_("Name"); ?></label>
<input title="<?php p_("Name");?>" id="username" name="username" accesskey="n" type="text" <?php 
if (isset($COMMENT_NAME)) echo "value=\"$COMMENT_NAME\""; ?> />
</div>
<div>
<label style="width: 40%" for="homepage"><?php p_("Homepage"); ?></label>
<input title="<?php p_("URL");?>" id="homepage" name="homepage" accesskey="h" type="text" <?php 
if (isset($COMMENT_URL)) echo "value=\"$COMMENT_URL\""; ?> />
</div>
<div>
<label style="width: 40%" for="email"><?php p_("E-Mail"); ?></label>
<input title="<?php p_("E-mail");?>" id="email" name="email" accesskey="e" type="text" <?php 
if (isset($COMMENT_EMAIL)) echo "value=\"$COMMENT_EMAIL\""; ?> />
</div>
<div>
<label for="showemail"><?php p_("Display my e-mail address"); ?></label>
<input title="<?php p_("Display e-mail address");?>" id="showemail" name="showemail" type="checkbox" <?php 
if (isset($COMMENT_SHOWEMAIL)) echo "checked=\"checked\""; ?> />
</div>
<div>
<label for="remember"><?php p_("Remember me"); ?></label>
<input title="<?php p_("Remember me");?>" id="remember" name="remember" type="checkbox" checked="checked" />
</div>
<div class="form_buttons">
<input class="comment_submit" name="submit" id="submit" type="submit" value="<?php p_("Post comment"); ?>" />
<!--<input name="clear" id="clear" type="reset" value="<?php p_("Clear"); ?>" />-->
</div>
<?php EventRegister::instance()->activateEventFull($tmp=false, "commentform", "FormEnd");?>
</form>
</fieldset>
<?php EventRegister::instance()->activateEventFull($tmp=false, "commentform", "AfterForm");?>
</div>
