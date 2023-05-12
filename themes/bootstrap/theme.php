<?php

use LnBlog\Ui\Theme;

return new Theme(
    basename(__DIR__),
    'Bootstrap',
    'A Bootstrap-based theme',
    function (Page $page): void {
        $page->addPackage('bootstrap');
    }
);
