<?php if ($ERROR): ?>
    <div class="error"><?php echo $ERROR?></div>
<?php endif ?>

<?php if ($SHOW_FORM): ?>
    <form method="post">
        <?php $this->outputCsrfField() ?>
        <div>
            <label>
                <span><?php p_("Confirm e-mail:")?></span>
                <input id="email" type="text" name="email"
                       value="<?php echo $this->escape($EMAIL)?>" />
            </label>
        </div>
        <div>
            <label>
                <span><?php p_("New password:")?></span>
                <input id="password" type="password" name="password"
                       value="<?php echo $this->escape($PASSWORD)?>" />
            </label>
        </div>
        <div>
            <label>
                <span><?php p_("Confirm password:")?></span>
                <input id="confirm-password" type="password" name="confirm-password"
                       value="<?php echo $this->escape($CONFIRM_PASSWORD)?>" />
            </label>
        </div>
        <input type="hidden" name="user"
               value="<?php echo $this->escape($USERNAME)?>" />
        <input type="hidden" name="token"
               value="<?php echo $this->escape($TOKEN)?>" />
        <div>
            <button><?php p_('Reset Password')?></button>
        </div>
    </form>
<?php endif ?>
