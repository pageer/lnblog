<?php

$this->block('main', function ($vars) {
    extract($vars);
    if ($BIO_PIC): ?>
        <div class="bio_picture">
            <img src="<?php echo $BIO_PIC ?>" alt="" />
        </div>
    <?php endif ?>
    <div class="bio_content">
        <?php echo $BIO ?>
    </div>
    <?php
    $this->showBlock('bio.style');
});

$this->block('bio.style', function () { ?>
    <style type="text/css">
        .bio_picture img {
            display: block;
            margin: 0 auto;
            border: 1px solid black;
            box-shadow: 1px 1px 1px 1px rgba(0, 0, 0, .5);
            max-width: 100%;
            border-radius: 5px;
        }
        .bio_content {
            margin-top: 1em;
        }
    </style><?php
});
