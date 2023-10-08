<li>
    <a href="<?php echo $BLOG_EDIT_URL?>">
        <?php p_("Blog RegEx blacklist") ?>
    </a>
</li>
<?php if ($usr->isAdministrator()): ?>
<li>
    <a href="<?php echo $GLOBAL_EDIT_URL?>">
        <?php p_("Global RegEx blacklist") ?>
    </a>
</li>
<?php endif ?>
