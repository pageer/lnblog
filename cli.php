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

$short_options = '';
$long_options = [];

# Run "cron" tasks.  Runs any checks and tasks that should be done 
# on a regular basis, e.g. every 15 minutes or so.
$short_options .= 'c';
$long_options[] = 'cron';

# Upgrade all blogs from the command line.
$short_options .= 'u::';
$long_options[] = 'upgrade::';

$options = getopt($short_options, $long_options);

if (isset($options['c']) || isset($options['cron'])) {
    $task_manager = new TaskManager();
    $task_manager->runPendingTasks();
    exit(0);
}

if (isset($options['u']) || isset($options['upgrade'])) {
    $status = 0;
    $blog_id = $options['u'] ?? $options['upgrade'];
    $blogs = SystemConfig::instance()->blogRegistry();

    if ($blog_id && isset($blogs[$blog_id])) {
        $blogs = [$blog_id => $blogs[$blog_id]];
    }

    foreach ($blogs as $id => $urlpath) {
        $blog = new Blog($urlpath->path());
        echo spf_('Upgrading blog %1$s at %2$s...', $blog->blogid, $blog->home_path);
        try {
            $result = $blog->upgradeWrappers();
        } catch (\Exception $e) {
            $result = [$e->getMessage()]
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
