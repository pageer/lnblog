<?php 
session_start();
$pages = new WebPages();
$pages->showdrafts();
