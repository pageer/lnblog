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
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><?php echo $this->escape($HEADER)?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <?php foreach ($LINKS as $item): ?>
                <li class="nav-item">
                <?php if (is_string($item)): ?>
                    <?php echo $item ?>
                <?php else: ?>
                <a href="<?php echo $item['url']?>" 
                    class="nav-link"
                    title="<?php echo $this->escape($item['description'])?>">
                    <?php echo $this->escape($item['name']) ?>
                </a>
                <?php endif ?>
                </li>
            <?php endforeach ?>
            </ul>
        </div>
    </div>
</nav><?php
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
