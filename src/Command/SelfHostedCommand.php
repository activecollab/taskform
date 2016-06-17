<?php

/*
 * This library is free software, and it is part of the Active Collab TaskForm project. Check LICENSE for details.
 *
 * (c) A51 doo <info@activecollab.com>
 */

namespace ActiveCollab\TaskForm\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package ActiveCollab\TaskForm
 */
class SelfHostedCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('self-hosted')
            ->setDescription('Configure connection to self-hosted Active Collab')
            ->addArgument('url', InputArgument::REQUIRED, 'URL of your Active Collab')
            ->addArgument('email', InputArgument::REQUIRED, 'Your email address')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Your password')
            ->addOption('dont-verify-ssl-peer', '', InputArgument::OPTIONAL, 'Skip SSL peer verification');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
