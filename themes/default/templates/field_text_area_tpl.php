<?php if ($LABEL): ?>
<label>
<span><?php echo htmlspecialchars($LABEL)?></span>
<?php endif?>
<textarea name="<?php echo htmlspecialchars($NAME)?>"
<?php
foreach ($ATTRIBUTES as $attr => $value):
    echo "$attr=\"" . htmlspecialchars($value) . '" ';
endforeach;
?>
><?php echo htmlspecialchars($VALUE)?></textarea>
<?php if ($LABEL): ?>
</label>
<?php endif;
