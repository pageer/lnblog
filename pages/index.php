<?php
session_start();
require_once __DIR__.'/../blogconfig.php';
$pages = new WebPages();
$pages->routeRequest();