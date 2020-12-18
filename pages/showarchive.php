<?php
initialize_session();
$page = new WebPages();
$page->routeRequest('showarchive');
