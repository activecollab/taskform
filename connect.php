<?php

/*
 * This library is free software, and it is part of the Active Collab TaskForm project. Check LICENSE for details.
 *
 * (c) A51 doo <info@activecollab.com>
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * @package ActiveCollab.tasks
 */
if (php_sapi_name() != 'cli') {
    print "This script is available via CLI only\n";
    exit(1);
}

require_once 'vendor/autoload.php';

$application = new \Symfony\Component\Console\Application('Active Collab TaskForm', '1.0.0');
$application->add(new \ActiveCollab\TaskForm\Command\ConnectCloudCommand());
$application->add(new \ActiveCollab\TaskForm\Command\ConnectSelfHostedCommand());
$application->run();
