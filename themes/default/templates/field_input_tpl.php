<?php if ($LABEL): ?>
<label><span><?php echo htmlspecialchars($LABEL)?></span>
<?php elseif ($SEPARATE_LABEL && isset($ATTRIBUTES['id'])): ?>
<label for="<?php echo htmlspecialchars($ATTRIBUTES['id'])?>">
    <?php echo htmlspecialchars($LABEL)?>
</label>
<?php endif ?>
<input type="<?php echo $TYPE?>" 
       name="<?php echo htmlspecialchars($NAME)?>" 
       <?php if ($TYPE == 'checkbox'): ?>
           value="1"
            <?php if ($VALUE): ?>
               checked="<?php echo $VALUE ? 'checked' : '' ?>"
            <?php endif ?>
       <?php else: ?>
           value="<?php echo htmlspecialchars($VALUE)?>"
       <?php endif ?>
<?php
foreach ($ATTRIBUTES as $attr => $value):
    echo "$attr=\"" . htmlspecialchars($value) . '" ';
endforeach;
?>
/>
<?php if ($LABEL): ?>
</label>
<?php endif; 
