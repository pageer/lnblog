<?php
# File: cli.php
# This script is the entry point to the command-line interface for LnBlog.
# This is intended primarily for administrative purposes, but is also used
# for selected integrations and running scheduled tasks

require_once dirname(__FILE__) . "/blogconfig.php";
use LnBlog\Tasks\TaskManager;

$short_options = '';
$long_options = [];

# Run "cron" tasks.  Runs any checks and tasks that should be done 
# on a regular basis, e.g. every 15 minutes or so.
$short_options .= 'c';
$long_options[] = 'cron';

$options = getopt($short_options, $long_options);

if (isset($options['c']) || isset($options['cron'])) {
    $task_manager = new TaskManager();
    $task_manager->runPendingTasks();

}
