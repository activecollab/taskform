<?php

/*
 * This library is free software, and it is part of the Active Collab TaskForm project. Check LICENSE for details.
 *
 * (c) A51 doo <info@activecollab.com>
 */

use ActiveCollab\TaskForm\Command\ConnectCloudCommand;
use ActiveCollab\TaskForm\Command\ConnectSelfHostedCommand;
use Symfony\Component\Console\Application;

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * @package ActiveCollab.tasks
 */
if (php_sapi_name() != 'cli') {
    print "This script is only available via Command Line (CLI)\n";
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

$application = new Application('Active Collab TaskForm', '1.0.0');
$application->add(new ConnectCloudCommand());
$application->add(new ConnectSelfHostedCommand());
$application->run();
