<?php
initialize_session();
$pages = new WebPages();
$pages->routeRequest('showarticles');
