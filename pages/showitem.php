<?php
session_start();
$page = new WebPages();
if (isset($_GET['action'])) {
    //$page->showblog();
$page->routeRequest();
} else {
    $page->showitem();
}
