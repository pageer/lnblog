<h2><?php echo $FORM_TITLE; ?></h2>
<?php if (isset($FORM_MESSAGE)): ?>
<p><?php echo $FORM_MESSAGE; ?></p>
<?php endif ?>
<form class="login-form" method="post" action="<?php echo $FORM_ACTION?>">
<?php $this->outputCsrfField() ?>
    <label for="<?php echo $UNAME; ?>"><?php p_('Username');?></label>
    <input type="text" id="<?php echo $UNAME?>" name="<?php echo $UNAME?>" value=""<?php if (isset($UNAME_VALUE)) echo "value=\"$UNAME_VALUE\""?> autofocus />
    <label for="<?php echo $PWD?>"><?php p_('Password')?></label>
    <input type="password" id="<?php echo $PWD?>" name="<?php echo $PWD?>" <?php if (isset($PWD_VALUE)) echo "value=\"$PWD_VALUE\""?>/>
    <input type="hidden" name="referer" id="referer" value="<?php echo $REF?>" />
    <input type="submit" value="<?php p_('Submit');?>" />
    <div class="forgot-password-block">
        <a id="forgot-password-link" href="?action=forgot">
            <?php p_("Forgot password?") ?>
        </a>
    </div>
</form>
<script type="application/javascript">
    $(document).ready(function() {
        $('#forgot-password-link').on('click', function() {
            var username = $('#<?php echo $UNAME?>').val();
            if (username) {
                var href = $(this).attr('href');
                window.location = href + '&user=' + username;
                return false;
            }
        });
    });
</script>
