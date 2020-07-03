<h2><?php echo $FORM_TITLE; ?></h2>
<?php if (isset($FORM_MESSAGE)) { ?>
<p><?php echo $FORM_MESSAGE; ?></p>
<?php } ?>
<form class="centered" method="post" action="<?php echo $FORM_ACTION; ?>">
<?php $this->outputCsrfField() ?>
<div>
	<label for="<?php echo $UNAME; ?>"><?php p_('Username');?></label>
	<input type="text" id="<?php echo $UNAME; ?>" name="<?php echo $UNAME; ?>" <?php if (isset($UNAME_VALUE)) echo 'value="'.$UNAME_VALUE.'" '; ?> autofocus />
</div>
<div>
	<label for="<?php echo $PWD; ?>"><?php p_('Password');?></label>
	<input type="password" id="<?php echo $PWD; ?>" name="<?php echo $PWD; ?>" <?php if (isset($PWD_VALUE)) echo 'value="'.$PWD_VALUE.'" '; ?>/>
</div>
<input type="hidden" name="referer" id="referer" value="<?php echo $REF; ?>" />
<div><span class="basic_form_submit"><input type="submit" value="<?php p_('Submit');?>" /></span>
<span class="basic_form_clear"><input type="reset" value="<?php p_('Clear');?>" /></span></div>
</form>
<div class="forgot-password-block">
    <a id="forgot-password-link" href="?action=forgot">
        <?php p_("Forgot password?") ?>
    </a>
</div>
<script type="application/javascript">
    $(document).ready(function() {
        $('#forgot-password-link').on('click', function() {
            var username = $('#<?php echo $UNAME?>').val();
            if (username) {
                var href = $(this).attr('href');
                window.location = href + '&user=' + username;
                console.log(href + '&user' + username);
                return false;
            }
        });
    });
</script>
<style type="text/css">
.forgot-password-block {
    text-align: center;
    margin-top: 3em;
}
</style>
