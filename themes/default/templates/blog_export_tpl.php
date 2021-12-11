<?php

$this->block(
    'main', function ($vars) {
        extract($vars, EXTR_OVERWRITE); 
        ?>
        <?php if (!empty($ERRORS)): ?>
            <h4><?php p_('Error!')?></h4>
            <p class="error">
            <?php foreach ($ERRORS as $error):
                echo $error . ' ';
            endforeach; ?>
            </p>
        <?php endif;?>

        <form method="<?php echo $METHOD?>">
            <?php $this->outputCsrfField() ?>
            <h2><?php p_('Export Blog')?></h2>
            <p>
                <?php p_('Export the selected blog as an XML file.') ?>
            </p>
            <p>
                <?php p_('Currently, the only supported format is WordPress eXtended RSS (WXR).') ?>
            </p>
            <?php echo $FIELDS['blog_id']->render(
                $PAGE, [
                    'label' => _('Blog to export'),
                ]
            ); ?>

            <input type="submit" id="do-export" value="<?php p_('Start Export')?>" />
        </form>
        <?php
    }
);
