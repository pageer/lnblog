<?php
$tooltip = _("Search for posts containing a space-separated list of words. If the search sting is enclosed in forward slashes, it will be treated as a regular expression.");
$placeholder = $LABEL_IN_BOX ? ('placeholder="' . htmlspecialchars($LABEL).  '"') : '';
?>
<form method="<?php echo $METHOD?>" action="<?php echo $FORM_URL?>">
    <fieldset style="border: 0">
        <?php if ($LABEL && !$LABEL_IN_BOX): ?>
        <label for="sb_search_terms" title="<?php echo $tooltip?>"><?php echo $LABEL?></label>
        <?php endif; ?>
        <input type="text" id="sb_search_terms" name="<?php echo $SEARCH_BOXES['visible']?>" <?php echo $placeholder?> />
        <?php foreach ($SEARCH_BOXES['hidden'] as $name => $val): ?>
        <input type="hidden" name="<?php echo $name?>" value="<?php echo $val?>" />
        <?php endforeach;?>
        <input type="submit" value="<?php p_("Search"); ?>" />
    </fieldset>
</form>
