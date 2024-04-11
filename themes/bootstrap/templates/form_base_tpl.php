<form method="<?php echo $METHOD?>" action="<?php echo $ACTION?>"
<?php
foreach ($ATTRIBUTES as $attr => $value):
    echo $this->escape($attr). "=\"" . $this->escape($value) . '" ';
endforeach;
?>
>
    <?php
    if (!empty($SUPPRESS_CSRF)):
        $this->outputCsrfField();
    endif;
    ?>
    <?php foreach ($FIELDS as $field): ?>
        <div class="<?php $ROW_CLASS?>">
            <?php echo $field->render($PAGE)?>
        </div>
    <?php endforeach ?>
    <?php if ($SUBMIT_BUTTON):?>
        <div class="<?php $ROW_CLASS?>">
            <input type="submit" name="<?php $this->escape($SUBMIT_NAME)?>" value="<?php $this->escape($SUBMIT_BUTTON)?>" />
        </div>
    <?php endif ?>
</form>
