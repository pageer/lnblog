<?php
initialize_session();
$page = new WebPages();
$page->routeRequestWithDefault('showitem');
