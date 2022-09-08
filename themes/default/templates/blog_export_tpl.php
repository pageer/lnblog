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
            <h4><?php p_('Format notes')?></h4>
            <p>
                <?php p_('RSS 1.0 support is limited to content - author, title, and entry text.') ?>
                <?php p_('RSS 2.0 and Atom support most other entry metadata, including date, enclosure, and tags.') ?>
                <?php p_('WordPress eXtended RSS (WXR) is the only format that includes replies in the export file.  However, this format is WordPress-specific and may not be supported by other software.') ?>
            </p>
            <?php echo $FIELDS['blog_id']->render(
                $PAGE, [
                    'label' => _('Blog to export'),
                ]
            ); ?>
            <?php echo $FIELDS['format']->render(
                $PAGE, [
                    'label' => _('Export format'),
                ]
            ); ?>

            <input type="submit" id="do-export" value="<?php p_('Start Export')?>" />
        </form>
        <?php
    }
);
