<h1>
    <a href="<?php echo $URL?>" title="<?php echo $DESCRIPTION?>">
        <?php if ($LOGO): ?>
            <img src="<?php echo $LOGO?>" alt="<?php echo $NAME?>">
        <?php else: ?>
            <?php echo $NAME; ?>
        <?php endif; ?>
    </a>
</h1>
<?php if ($SHOW_DESCRIPTION): ?>
    <h3><?php echo $DESCRIPTION?></h3>
<?php endif;
