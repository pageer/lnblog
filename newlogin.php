<?php

session_start();
require_once __DIR__."/blogconfig.php";
$admin = new AdminPages();
$admin->newlogin();
