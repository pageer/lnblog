<h2><?php echo $CONFIRM_TITLE; ?></h2>
<p><?php echo $CONFIRM_MESSAGE; ?></p>
<form method="post" action="<?php echo $CONFIRM_PAGE; ?>">
    <?php $this->outputCsrfField() ?>
<div>
    <input type="submit" id="<?php echo $OK_ID; ?>"
        name="<?php echo $OK_ID; ?>" 
        value="<?php echo $OK_LABEL; ?>" />
    <input type="submit" id="<?php echo $CANCEL_ID; ?>"
        name="<?php echo $CANCEL_ID; ?>" 
        value="<?php echo $CANCEL_LABEL; ?>" />
    <input type="hidden" name="confirm_form" value="1" />
    <?php if (! empty($PASS_DATA_ID)): ?>
        <?php if (is_array($PASS_DATA)): ?>
            <?php foreach ($PASS_DATA as $data_key => $data_item): ?>
                <input type="hidden" id="<?php echo $PASS_DATA_ID; ?>" 
                    name="<?php echo $PASS_DATA_ID . '-' . $data_key; ?>"
                    value="<?php echo $data_item; ?>" />
            <?php endforeach; ?>
        <?php else: ?>
            <input type="hidden" id="<?php echo $PASS_DATA_ID; ?>" 
                name="<?php echo $PASS_DATA_ID; ?>"
                value="<?php echo $PASS_DATA; ?>" />
        <?php endif; ?>
    <?php endif; ?>
</div>
</form>
