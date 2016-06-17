<?php

/*
 * This library is free software, and it is part of the Active Collab TaskForm project. Check LICENSE for details.
 *
 * (c) A51 doo <info@activecollab.com>
 */

namespace ActiveCollab\TaskForm\Command;

use ActiveCollab\SDK\Authenticator\Cloud;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @package ActiveCollab\TaskForm
 */
class ConnectCloudCommand extends ConnectCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('connect:cloud')
            ->setDescription('Configure connection to Active Collab hosted on activecollab.com');

        $this->configureAuthArgumentsAndOptions();
    }

    /**
     * {@inheritdoc}
     */
    protected function getToken(InputInterface $input, OutputInterface $output)
    {
        $email = $this->getEmail($input);
        $password = $this->getPassword($input, $output);
        $ssl_verify_peer = $this->getSslVerifyPeer($input);

        $authenticator = new Cloud('Active Collab', 'TaskForm', $email, $password);
        $authenticator->setSslVerifyPeer($ssl_verify_peer);

        $output->writeln('Authenticating...');
        $output->writeln('');

        $accounts = $authenticator->getAccounts();
        $account_id = $this->getAccountId($input, $output, $accounts);
        $output->writeln("You have selected account <comment>#{$account_id}</comment>. Fetching token...");

        $token = $authenticator->issueToken($account_id);
        $output->writeln("Token for <comment>$email</comment> has been issued.");
        $output->writeln('');

        return $token;
    }

    /**
     * Ask user to pick an account that they want to authenticate with.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @param  array           $accounts
     * @return int
     */
    private function getAccountId(InputInterface $input, OutputInterface $output, array $accounts)
    {
        if (empty($accounts)) {
            throw new RuntimeException("You don't have any Active Collab accounts to choose from");
        }

        $output->writeln('You have access to these accounts:');
        $output->writeln('');

        foreach ($accounts as $account) {
            $output->writeln('    <comment>*</comment> ' . $account['name'] . ' <comment>#' . $account['id'] . '</comment>');
        }

        $output->writeln('');

        $account_id = (int) trim($this->getHelper('question')->ask($input, $output, (new Question("Which one would you like to use? Please enter account #:\n"))), '#');

        if ($account_id) {
            $account_found = false;

            foreach ($accounts as $account) {
                if ($account['id'] == $account_id) {
                    $account_found = true;
                    break;
                }
            }

            if (empty($account_found)) {
                throw new RuntimeException("You don't have access to account #{$account_id}");
            }

            return $account_id;
        } else {
            throw new RuntimeException('Account ID is not set');
        }
    }
}
