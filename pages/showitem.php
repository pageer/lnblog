<?php
session_start();
$page = new WebPages();
if (isset($_GET['action'])) {
	$page->showblog();
} else {
	$page->showitem();
}
