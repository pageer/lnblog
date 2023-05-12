<?php

$this->block('menubar.sitemap.editlink', function ($vars) {
    extract($vars); ?>
    <li>
        <a href="<?php echo $this->escape($EDIT_URL)?>">
            <?php p_("Edit custom sitemap")?>
        </a>
    </li>
    <?php
});

$this->block('menubar.sitemap', function ($vars) {
    extract($vars); ?>
    <h2><?php echo $this->escape($HEADER)?></h2>
    <ul>
    <?php foreach ($LINKS as $item): ?>
        <li>
        <?php if (is_string($item)): ?>
            <?php echo $item ?>
        <?php else: ?>
        <a href="<?php echo $item['url']?>" title="<?php echo $this->escape($item['description'])?>">
            <?php echo $this->escape($item['name']) ?>
        </a>
        <?php endif ?>
        </li>
    <?php endforeach ?>
    </ul><?php
});

$this->block('main', function ($vars) {
    extract($vars);
    if (!empty($SHOW_EDIT_LINK)) {
        $this->showBlock('menubar.sitemap.editlink');
    } elseif (!empty($RAW_MARKUP)) { ?>
        <ul><?php echo $RAW_MARKUP?></ul><?php
    } else {
        $this->showBlock('menubar.sitemap');
    }
});
