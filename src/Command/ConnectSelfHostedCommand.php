<?php

/*
 * This library is free software, and it is part of the Active Collab TaskForm project. Check LICENSE for details.
 *
 * (c) A51 doo <info@activecollab.com>
 */

namespace ActiveCollab\TaskForm\Command;

use ActiveCollab\SDK\Authenticator\SelfHosted;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package ActiveCollab\TaskForm
 */
class ConnectSelfHostedCommand extends ConnectCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('connect:self-hosted')
            ->setDescription('Configure connection to ActiveCollab hosted on your server')
            ->addArgument('url', InputArgument::REQUIRED, 'URL where ActiveCollab is installed');

        $this->configureAuthArgumentsAndOptions();
    }

    /**
     * {@inheritdoc}
     */
    protected function getToken(InputInterface $input, OutputInterface $output)
    {
        $url = $this->getUrl($input);
        $email = $this->getEmail($input);
        $password = $this->getPassword($input, $output);
        $ssl_verify_peer = $this->getSslVerifyPeer($input);

        $authenticator = new SelfHosted('ActiveCollab', 'TaskForm', $email, $password, $url);
        $authenticator->setSslVerifyPeer($ssl_verify_peer);

        $output->writeln('Authenticating...');
        $output->writeln('');

        $token = $authenticator->issueToken();
        $output->writeln("Token for <comment>$email</comment> has been issued.");
        $output->writeln('');

        return $token;
    }

    /**
     * Return URL argument.
     *
     * @param  InputInterface $input
     * @return string
     */
    protected function getUrl(InputInterface $input)
    {
        $url = $input->getArgument('url');

        if ($url) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            } else {
                throw new RuntimeException('Invalid URL');
            }
        } else {
            throw new RuntimeException('URL is required');
        }
    }
}
