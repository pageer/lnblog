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
if ($ERRORS) {
    foreach ($ERRORS as $error): ?>
        <span class="error"><?php echo $error?></span><?php
    endforeach;
} else {
    p_("A comment body is required.  No HTML code allowed.  URLs starting with http:// or ftp:// will be automatically converted to hyperlinks."); 
}?>
</p>
<?php
EventRegister::instance()->activateEventFull($tmp=false, "commentform", "BeforeForm");?>
<fieldset>
    <form id="commentform" class="comment-form" method="<?php echo $METHOD?>" 
      action="<?php echo $ACTION;?>#<?php echo $ANCHOR?>" accept-charset="<?php echo DEFAULT_CHARSET;?>">
    <?php $this->outputCsrfField() ?>
    <?php EventRegister::instance()->activateEventFull($tmp=false, "commentform", "FormBegin");?>
    <div class="comment-metadata subject">
        <?php 
        echo $FIELDS['subject']->render(
            $PAGE, [
                'label' => _('Comment title'),
                'sep_label' => true,
                'title' => _('Comment title'),
                'id' => 'subject',
                'accesskey' => 's',
            ]
        ) ?>
    </div>
    <div class="body">
        <?php 
        echo $FIELDS['data']->render(
            $PAGE, [
                'label' => _('Comment body'),
                'sep_label' => true,
                'title' => _('Comment body'),
                'id' => 'data',
                'accesskey' => 'b',
                'rows' => 10,
                'cols' => 20,
            ]
        ) ?>
    </div>
    <div class="comment-metadata name">
        <?php 
        echo $FIELDS['username']->render(
            $PAGE, [
                'label' => _('Name'),
                'sep_label' => true,
                'title' => _('Name'),
                'id' => 'username',
                'accesskey' => 'n',
            ]
        ) ?>
    </div>
    <div class="comment-metadata homepage">
        <?php 
        echo $FIELDS['homepage']->render(
            $PAGE, [
                'label' => _('Homepage'),
                'sep_label' => true,
                'title' => _('URL'),
                'id' => 'homepage',
                'accesskey' => 'h',
            ]
        ) ?>
    </div>
    <div class="comment-metadata email">
        <?php 
        echo $FIELDS['email']->render(
            $PAGE, [
                'label' => _('E-Mail'),
                'sep_label' => true,
                'title' => _('E-Mail'),
                'id' => 'email',
                'accesskey' => 'e',
            ]
        ) ?>
    </div>
    <div class="comment-check show-email">
        <?php 
        echo $FIELDS['showemail']->render(
            $PAGE, [
                'title' => _('Display e-mail address'),
                'id' => 'showemail',
            ]
        ) ?>
        <label for="showemail"><?php p_("Display my e-mail address")?></label>
    </div>
    <div class="comment-check remember">
        <?php 
        echo $FIELDS['remember']->render(
            $PAGE, [
                'title' => _('Remember me'),
                'id' => 'remember',
            ]
        ) ?>
        <label for="remember"><?php p_("Remember me")?></label>
    </div>
    <div class="form_buttons">
        <input class="comment_submit" name="submit" id="submit" type="submit" value="<?php p_("Post comment")?>" />
    </div>
    <?php EventRegister::instance()->activateEventFull($tmp=false, "commentform", "FormEnd");?>
</form>
</fieldset>
<?php EventRegister::instance()->activateEventFull($tmp=false, "commentform", "AfterForm");?>
</div>
