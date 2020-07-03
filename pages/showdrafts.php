<?php 
initialize_session();
$pages = new WebPages();
$pages->routeRequestWithDefault('drafts');
