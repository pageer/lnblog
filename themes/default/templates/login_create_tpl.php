<style>
.login-edit-form label {
    display: block;
}

.login-edit-form input {
    max-width: 90%;
}
</style>

<h2><?php echo $FORM_TITLE; ?></h2>
<?php if (isset($FORM_MESSAGE)): ?>
<p><?php echo $FORM_MESSAGE; ?></p>
<?php endif ?>

<form class="login-edit-form" method="post" action="<?php echo $FORM_ACTION; ?>">
    <?php $this->outputCsrfField() ?>
    <?php if (isset($UNAME)): ?>
    <div>
        <label for="<?php echo $UNAME; ?>"><?php p_('Username'); ?></label>
        <input type="text" id="<?php echo $UNAME; ?>" name="<?php echo $UNAME; ?>" 
            value="<?php echo $UNAME_VALUE ?? ''?>"
            <?php if (isset($DISABLE_UNAME)):?>
            readonly="readonly" style="background-color: #E0E0E0;"
            <?php endif; ?>
             />
    </div>
    <?php endif; ?>
    <div>
        <label for="passwd"><?php p_('Password'); ?></label>
        <input type="password" id="passwd" name="passwd" value="<?php echo $PWD_VALUE ?? '' ?>" />
    </div>
    <div>
        <label for="confirm"><?php p_('Confirm'); ?></label>
        <input type="password" id="confirm" name="confirm" value="<?php echo $CONFIRM_VALUE ?? '' ?>" />
    </div>
    <div>
        <label for="fullname"><?php p_('Real name');?></label>
        <input type="text" id="fullname" name="fullname" size="50" value="<?php echo $FULLNAME_VALUE ?? '' ?>" />
    </div>
    <div>
        <label for="email"><?php p_('E-Mail');?></label>
        <input type="text" id="email" name="email" size="50" value="<?php $EMAIL_VALUE ?? '' ?>" />
    </div>
    <div>
        <label for="homepage"><?php p_('Homepage');?></label>
        <input type="text" id="homepage" name="homepage" size="50" value="<?php echo $HOMEPAGE_VALUE ?? '' ?>" />
    </div>
    <div>
        <label for="profile_url"><?php p_('Custom profile URL (optional)');?></label>
        <input type="text" id="profile_url" name="profile_url" size="50" value="<?php echo $PROFILEPAGE_VALUE ?? '' ?>" />
    </div>
    <?php foreach ($CUSTOM_FIELDS as $key=>$val): ?>
    <div>
        <label for="<?php echo $key?>"><?php echo htmlspecialchars($val)?></label>
        <input type="text" id="<?php echo $key?>" name="<?php echo $key?>" size="50"
            value="<?php echo !empty($CUSTOM_VALUES[$key]) ? htmlspecialchars($CUSTOM_VALUES[$key]) : ''?>" />
    </div>
    <?php endforeach ?>
    <?php if (isset($UPLOAD_LINK) && isset($PROFILE_EDIT_LINK)): ?>
    <p style="text-align: center">
    <a href="<?php echo $PROFILE_EDIT_LINK?>"><?php echo $PROFILE_EDIT_DESC?></a><br />
    <a href="<?php echo $UPLOAD_LINK?>"><?php echo $UPLOAD_DESC?></a>
    </p>
    <?php endif ?>
    <div>
        <span class="basic_form_submit"><input type="submit" value="<?php p_('Submit');?>" /></span>
    </div>
</form>
<script type="text/javascript">
<!--
<?php if (! isset($UNAME)) { ?>
var elem = document.getElementById('<?php echo $PWD; ?>');
elem.focus();
<?php } else { ?>
var elem = document.getElementById('<?php echo $UNAME; ?>');
elem.focus();
<?php } ?>
-->
</script>
