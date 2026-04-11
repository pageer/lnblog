<?php
# File: cli.php
# This script is the entry point to the command-line interface for LnBlog.
# This is intended primarily for administrative purposes, but is also used
# for selected integrations and running scheduled tasks

if (php_sapi_name() !== "cli") {
    echo "Error: This script must be run from the command line!\n";
    exit(1);
}

require_once dirname(__FILE__) . "/blogconfig.php";
use LnBlog\Tasks\TaskManager;

$short_options = [];
$long_options = [];

# Show usage information.
$short_options[] = 'h';
$long_options[] = 'help';

# Run "cron" tasks.  Runs any checks and tasks that should be done
# on a regular basis, e.g. every 15 minutes or so.
$short_options[] = 'c';
$long_options[] = 'cron';

# Upgrade all blogs from the command line.
$short_options[] = 'u::';
$long_options[] = 'upgrade::';

$options = getopt(implode('', $short_options), $long_options);

function print_help_message($options) {//use ($short_options, $long_options) {
    global $short_options, $long_options;
    echo "Usage: php " . basename(__FILE__) . " [OPTION]\n\nOptions:\n";
    foreach ($long_options as $idx => $long) {
        $long_name = rtrim($long, ':');
        $long_colons = substr($long, strlen($long_name));
        $long_display = "--$long_name";
        if ($long_colons === '::') $long_display .= '[=VALUE]';
        elseif ($long_colons === ':') $long_display .= '=VALUE';

        if (isset($short_options[$idx])) {
            $short = $short_options[$idx];
            $short_name = rtrim($short, ':');
            $short_colons = substr($short, strlen($short_name));
            $short_display = "-$short_name";
            if ($short_colons === '::') $short_display .= '[VALUE]';
            elseif ($short_colons === ':') $short_display .= ' VALUE';
            echo "  $short_display, $long_display\n";
        } else {
            echo "  $long_display\n";
        }
    }
    exit(0);
}

function upgrade_blog($options) {
    $status = 0;
    $blog_id = $options['u'] ?? $options['upgrade'];
    $blogs = SystemConfig::instance()->blogRegistry();

    if ($blog_id && isset($blogs[$blog_id])) {
        echo "Upgrading blog $blog_id...\n";
        $blogs = [$blog_id => $blogs[$blog_id]];
    } else {
        echo "Upgrading all blogs...\n";
    }

    foreach ($blogs as $id => $urlpath) {
        $blog = new Blog($urlpath->path());
        echo spf_('Upgrading blog %1$s at %2$s...', $blog->blogid, $blog->home_path);
        try {
            $result = $blog->upgradeWrappers();
        } catch (\Exception $e) {
            $result = [$e->getMessage()];
        }
        if (empty($result)) {
            echo _("success!") . PHP_EOL;
        } else {
            echo _("FAILURE!") . PHP_EOL;
            echo _("Failed to upgrade the following files:") . PHP_EOL;
            echo implode(PHP_EOL, $result) . PHP_EOL;
            $status += 1;
        }
    }

    exit($status);
}

switch (array_key_first($options)) {
    case 'h':
    case 'help':
        print_help_message($options);
        break;
    case 'c':
    case 'cron':
        $task_manager = new TaskManager();
        $task_manager->runPendingTasks();
        exit(0);
    case 'u':
    case 'upgrade':
        upgrade_blog($options);
        break;
    default:
        print_help_message($options);
}
