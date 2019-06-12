<?php 
session_start();
$pages = new WebPages();
$pages->routeRequestWithDefault('drafts');
