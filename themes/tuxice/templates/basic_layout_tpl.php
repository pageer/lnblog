<?php 

$this->extends('basic_layout_tpl.php');

$this->block(
    'main', function ($vars) {
    $this->showBlock('basiclayout.menubar');
    $this->showBlock('basiclayout.banner');
    ?><div style="clear:both"></div><?php
    $this->showBlock('basiclayout.content');
    $this->showBlock('basiclayout.sidebar');
    }
);
