<div id="ic_block">
<?php if ($ACCESSIBLE): ?>
    <label for="link">
        <?php pf_("Enter the %s of %s and %s.", $OPERATION, $RAND1, $RAND2)?>
    </label>
    <input type="text" name="link" id="link" />
<?php else: ?>
    <em><?php p_("Sorry, but you must have JavaScript enabled to post comments.")?></em>
    <input type="hidden" name="link" id="link" />
<?php endif ?>
    <input type="hidden" name="ictok" value="<?php echo $TOKEN?>" />
</div>
