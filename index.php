<?php
require_once __DIR__."/blogconfig.php";
initialize_session();
$admin = new AdminPages();
$admin->routeRequest();
