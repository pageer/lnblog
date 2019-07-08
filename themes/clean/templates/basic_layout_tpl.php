<?php

$this->extends('basic_layout_tpl.php');

$this->block('basiclayout.banner', function ($vars) {
    extract($vars, EXTR_OVERWRITE); 
    ?>
    <body>
    <!-- Site banner -->
    <div id="banner">
        <div id="responsive-menu">
            <a href="#">&#9776;</a>
        </div>
        <?php
        EventRegister::instance()->activateEventFull($tmp=false, "banner", "OnOutput");
        EventRegister::instance()->activateEventFull($tmp=false, "banner", "OutputComplete");

        $this->showBlock('basiclayout.menubar');
        ?>
    </div><?php
});

$this->block('basiclayout.sidebar', function ($vars) {
    extract($vars, EXTR_OVERWRITE); 
    ?>
        <!-- A sidebar -->
        <div id="sidebar">
            <div id="responsive-close">
                <a href="#">
                    <span class="close-label"><?php p_("Close menu")?></span>
                    <span class="x">&times;</span>
                </a>
            </div>
        <?php 
        EventRegister::instance()->activateEventFull($tmp=false, "sidebar", "OnOutput");
        EventRegister::instance()->activateEventFull($tmp=false, "sidebar", "OutputComplete");
        ?>
    </div><?php
});

$this->block('main', function ($vars) {
    extract($vars, EXTR_OVERWRITE); 
    ?>
    <body>
    <?php $this->showBlock('basiclayout.banner'); ?>
    <div id="main-wrapper">
    <?php
        $this->showBlock('basiclayout.content');
        $this->showBlock('basiclayout.sidebar');
    ?>
    </div>
    </body><?php
});
