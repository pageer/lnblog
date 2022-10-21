<?php
# This is for build validation purposes only, don't bother with includes.
$content = file_get_contents('blogconfig.php');
if (!$content) {
    echo "Error reading blogconfig.php";
    exit(1);
}

$matches = array();
$count = preg_match('/"PACKAGE_VERSION" *, *"([\d\.]+)"/', $content, $matches);

if ($count !== 1) {
    echo "Error determining package version";
    exit(1);
}

if (isset($_SERVER['argv'][1])) {
    $version_match = $_SERVER['argv'][1] === $matches[1];
    exit($version_match ? 0 : 1);
} else {
    echo $matches[1];
    exit(0);
}
