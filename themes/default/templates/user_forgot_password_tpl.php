<?php if ($ERROR): ?>
    <div class="error"><?php echo $ERROR?></div>
<?php endif ?>

<?php if ($SHOW_FORM): ?>
    <form method="post">
        <?php if ($USERNAME): ?>
        <input type="hidden" name="user"
               value="<?php echo $this->escape($USERNAME)?>" />
        <?php else: ?>
        <div>
            <label>
                <span><?php p_("Username:")?></span>
                <input id="username" type="text" name="user"
                       value="<?php echo $this->escape($USERNAME)?>" />
            </label>
        </div>
        <?php endif ?>
        <div>
            <label>
                <span><?php p_("Confirm user e-mail:")?></span>
                <input id="confirm-email" type="text" name="email"
                       value="<?php echo $this->escape($EMAIL)?>" />
            </label>
        </div>
        <div>
        <button><?php p_('Send Code')?></button>
        </div>
    </form>
<?php else: ?>
    <div><?php echo $MESSAGE?></div>
<?php endif ?>
