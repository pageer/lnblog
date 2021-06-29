<form method="<?php echo $METHOD?>" action="<?php echo $ACTION?>"
<?php
if (!empty($SUPPRESS_CSRF)):
    $this->outputCsrfField();
endif;
?>
<?php
foreach ($ATTRIBUTES as $attr => $value):
    echo "$attr=\"" . htmlspecialchars($value) . '" ';
endforeach;
?>
>
    <div class="<?php $ROW_CLASS?>">
        <?php
        foreach ($FIELDS as $field):
            $field->render();
        endforeach;
        ?>
    </div>
    <?php if ($SUBMIT_BUTTON):?>
        <div class="<?php $ROW_CLASS?>">
            <input type="submit" value="<?php htmlspecialchars($SUBMIT_BUTTON)?>" />
        </div>
    <?php endif ?>
</form>
