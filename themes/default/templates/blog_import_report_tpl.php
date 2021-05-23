<?php

$this->block(
    'main', function ($vars) {
    extract($vars);

    $error_block = function ($errors, $section) {
        if (!$errors) {
            return;
        }
        ?>
        <h3><?php echo $section ?></h3>
        <ul>
            <?php foreach ($errors as $err): ?>
            <li><?php echo $err?></li>
            <?php endforeach ?>
            </ul><?php
    };
    ?>
<h2><?php p_('Blog import status')?></h2>
<div class="import-report">
    <p>
        <?php pf_('Imported into %s.', $BLOG->name) ?>
        <br>
        <a href="<?php echo $BLOG->getURL()?>"><?php echo $BLOG->getURL()?></a>
    </p>
    <p><?php p_('The following items were imported successfully.')?></p>
    <ul>
        <li><?php p_('Published entries')?>:  <?php echo count($REPORT->entries)?></li>
        <li><?php p_('Draft entries')?>:  <?php echo count($REPORT->drafts)?></li>
        <li><?php p_('Articles')?>:  <?php echo count($REPORT->articles)?></li>
        <li><?php p_('Comments')?>:  <?php echo count($REPORT->comments)?></li>
        <li><?php p_('Pings')?>:  <?php echo count($REPORT->pings)?></li>
        <li><?php p_('User accounts')?>:  <?php echo count($REPORT->users)?></li>
    </ul>

    <?php
        $error_block($REPORT->entry_errors, _('Published entry errors'));
        $error_block($REPORT->draft_errors, _('Draft entry errors'));
        $error_block($REPORT->article_errors, _('Article errors'));
        $error_block($REPORT->comment_errors, _('Comment errors'));
        $error_block($REPORT->ping_errors, _('Ping errors'));
        $error_block($REPORT->user_errors, _('User errors'));
    ?>
</div>
    <?php
    }
);
