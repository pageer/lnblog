<?php
$tooltip = _("Search for posts containing a space-separated list of words. If the search sting is enclosed in forward slashes, it will be treated as a regular expression.");
$placeholder = $LABEL_IN_BOX ? ('placeholder="' . htmlspecialchars($LABEL).  '"') : '';
?>
<form method="<?php echo $METHOD?>" action="<?php echo $FORM_URL?>">
    <?php if ($LABEL && !$LABEL_IN_BOX): ?>
    <div class="mb-3">
        <label class="form-label" for="sb_search_terms" title="<?php echo $tooltip?>">
            <?php echo $LABEL?>
        </label>
    </div>
    <?php endif; ?>
    <div class="mb-3">
        <input type="text" class="form-control" id="sb_search_terms" name="<?php echo $SEARCH_BOXES['visible']?>" <?php echo $placeholder?> />
        <?php foreach ($SEARCH_BOXES['hidden'] as $name => $val): ?>
        <input type="hidden" name="<?php echo $name?>" value="<?php echo $val?>" />
        <?php endforeach;?>
    </div>
    <div class="mb-3">
        <input type="submit" class="btn btn-primary" value="<?php p_("Search"); ?>" />
    </div>
</form>
