<div id="commentsubmit">
<h3><a name="postcommentform">
<?php if (isset($PARENT_TITLE)) {
	pf_("Add your comments on %s", $PARENT_TITLE);
} else { 
	p_("Add your comments");
} ?>
</a></h3>
<p><?php 
if (isset($COMMENT_FORM_MESSAGE)) {
	echo $COMMENT_FORM_MESSAGE;
} else {
	p_("A comment body is required.  No HTML code allowed.  URLs starting with 
http:// or ftp:// will be automatically converted to hyperlinks."); 
}?></p>
<?php
global $EVENT_REGISTER;
$EVENT_REGISTER->activateEventFull($tmp=false, "commentform", "BeforeForm");?>
<fieldset>
<form id="commentform" method="post" action="<?php echo $FORM_TARGET;?>">
<?php $EVENT_REGISTER->activateEventFull($tmp=false, "commentform", "FormBegin");?>
<div>
<label class="basic_form_label" for="subject"><?php p_("Subject"); ?></label>
<input style="width: 70%" id="subject" name="subject" accesskey="s" <?php
if (isset($COMMENT_SUBJECT)) echo "value=\"$COMMENT_SUBJECT\""; ?> type="text" />
</div>
<div>
<textarea id="data" name="data" accesskey="d" rows="10" cols="20"><?php
if (isset($COMMENT_DATA)) echo $COMMENT_DATA;
?></textarea>
</div>
<div>
<label style="width: 40%" for="username"><?php p_("Name"); ?></label>
<input id="username" name="username" accesskey="n" type="text" <?php 
if (isset($COMMENT_NAME)) echo "value=\"$COMMENT_NAME\""; ?> />
</div>
<div>
<label style="width: 40%" for="url"><?php p_("Homepage"); ?></label>
<input id="url" name="url" accesskey="h" type="text" <?php 
if (isset($COMMENT_URL)) echo "value=\"$COMMENT_URL\""; ?> />
</div>
<div>
<label style="width: 40%" for="email"><?php p_("E-Mail"); ?></label>
<input id="email" name="email" accesskey="e" type="text" <?php 
if (isset($COMMENT_EMAIL)) echo "value=\"$COMMENT_EMAIL\""; ?> />
</div>
<div>
<label for="showemail"><?php p_("Display my e-mail address"); ?></label>
<input id="showemail" name="showemail" type="checkbox" <?php 
if (isset($COMMENT_SHOWEMAIL)) echo "checked=\"checked\""; ?> />
</div>
<div>
<label for="remember"><?php p_("Remember me"); ?></label>
<input id="remember" name="remember" type="checkbox" checked="checked" />
</div>
<div>
<span class="basic_form_submit"><input name="submit" id="submit" type="submit" value="<?php p_("Submit"); ?>" /></span>
<span class="basic_form_clear"><input name="clear" id="clear" type="reset" value="<?php p_("Clear"); ?>" /></span>
</div>
<?php $EVENT_REGISTER->activateEventFull($tmp=false, "commentform", "FormEnd");?>
</form>
</fieldset>
<?php $EVENT_REGISTER->activateEventFull($tmp=false, "commentform", "AfterForm");?>
</div>
