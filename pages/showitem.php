<?php
initialize_session();
$dir = basename(getcwd());
$action = $dir == 'trackback' ? 'tbping' : 'showitem';
$page = new WebPages();
$page->routeRequestWithDefault('showitem');
