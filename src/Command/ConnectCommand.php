<?php

/*
 * This library is free software, and it is part of the Active Collab TaskForm project. Check LICENSE for details.
 *
 * (c) A51 doo <info@activecollab.com>
 */

namespace ActiveCollab\TaskForm\Command;

use ActiveCollab\SDK\Client;
use ActiveCollab\SDK\ClientInterface;
use ActiveCollab\SDK\TokenInterface;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @package ActiveCollab\TaskForm\Command
 */
abstract class ConnectCommand extends Command
{
    /**
     * Get ActiveCollab SDK Client instance.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return TokenInterface
     */
    abstract protected function getToken(InputInterface $input, OutputInterface $output);

    /**
     * Configure authentication arguments and options.
     */
    protected function configureAuthArgumentsAndOptions()
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Your email address')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Your password')
            ->addOption('dont-verify-ssl-peer', '', InputArgument::OPTIONAL, 'Skip SSL peer verification')
            ->addOption('debug', '', InputOption::VALUE_NONE, 'Show debug information');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            if (is_file($this->getConfigFilePath())) {
                throw new RuntimeException("Config file '{$this->getConfigFilePath()}' already exists");
            }

            $token = $this->getToken($input, $output);

            $client = new Client($token);

            $this->checkUserRole($client);

            $project_id = $this->getProjectId($input, $output, $client);
            $output->writeln("You have selected project <comment>#{$project_id}</comment>. Writing config file...");

            $this->writeConfigFile($output, $token, $project_id);

            $output->writeln('');
            $output->writeln('All done, <info>connection to ActiveCollab has been configured</info>. Form can now be used to submit to task in Active Collab.');
        } catch (Exception $e) {
            $output->writeln('<error>Error</error>: ' . $e->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * Return email argument.
     *
     * @param  InputInterface $input
     * @return string
     */
    protected function getEmail(InputInterface $input)
    {
        $email = $input->getArgument('email');

        if ($email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            } else {
                throw new RuntimeException('Invalid email address');
            }
        } else {
            throw new RuntimeException('Email is required');
        }
    }

    /**
     * Return password, from option, or by asking user to provide a password.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return string
     */
    protected function getPassword(InputInterface $input, OutputInterface $output)
    {
        $password = $input->getOption('password');

        if (empty($password)) {
            if ($input->isInteractive()) {
                $password = $this->getHelper('question')->ask($input, $output, (new Question('Enter password: '))->setHidden(true)->setHiddenFallback(false));

                if (empty($password)) {
                    throw new RuntimeException('Password is required');
                }
            } else {
                throw new RuntimeException('Password is required');
            }
        }

        $output->writeln('');

        return $password;
    }

    /**
     * Return true if we should verify SSL peer.
     *
     * @param  InputInterface $input
     * @return bool
     */
    protected function getSslVerifyPeer(InputInterface $input)
    {
        $ssl_verify_peer = true;

        if ($input->getOption('dont-verify-ssl-peer')) {
            $ssl_verify_peer = false;
        }

        return $ssl_verify_peer;
    }

    /**
     * Check user session and user role.
     *
     * @param ClientInterface $client
     */
    public function checkUserRole(ClientInterface $client)
    {
        $response = $client->get('/user-session');

        if ($response->isJson()) {
            $user_session = $response->getJson();

            if (!empty($user_session['logged_user_id'])) {
                $response = $client->get("/users/{$user_session['logged_user_id']}");

                if ($response->isJson()) {
                    $user = $response->getJson();

                    if (empty($user['single']['class'])) {
                        throw new RuntimeException('User role could not be read');
                    } elseif ($user['single']['class'] == 'Client') {
                        throw new RuntimeException('Clients are not allowed to set up task forms');
                    }
                } else {
                    throw new RuntimeException('Invalid project names response');
                }
            } else {
                throw new RuntimeException('ID of logged user not found in user session');
            }
        } else {
            throw new RuntimeException('Invalid project names response');
        }
    }

    /**
     * Ask user to pick an account that they want to authenticate with.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @param  ClientInterface $client
     * @return int
     */
    protected function getProjectId(InputInterface $input, OutputInterface $output, ClientInterface $client)
    {
        $response = $client->get('projects/names');

        if ($response->isJson()) {
            $project_id_names_map = $response->getJson();
        } else {
            throw new RuntimeException('Invalid project names response');
        }

        if (empty($project_id_names_map)) {
            throw new RuntimeException("You don't have access to any of the projects in that ActiveåCollab");
        }

        $output->writeln('Here is a list of active projects that you have access to:');
        $output->writeln('');

        foreach ($project_id_names_map as $id => $name) {
            $output->writeln("    <comment>*</comment> {$name} <comment>#{$id}</comment>");
        }

        $output->writeln('');

        $project_id = (int) trim(
            $this->getHelper('question')->ask($input, $output, (new Question("Which one would you like to use? Please enter project #:\n"))),
            '#'
        );

        if ($project_id) {
            if (array_key_exists($project_id, $project_id_names_map)) {
                return $project_id;
            } else {
                throw new RuntimeException("You don't have access to project #{$project_id}");
            }
        } else {
            throw new RuntimeException('Account ID is not set');
        }
    }

    /**
     * Write settings to config file.
     *
     * @param OutputInterface $output
     * @param TokenInterface  $token
     * @param int             $project_id
     */
    protected function writeConfigFile(OutputInterface $output, TokenInterface $token, $project_id)
    {
        $config_file_path = $this->getConfigFilePath();

        $config_file_written = file_put_contents($config_file_path, "<?php\n\n" . 'return ' . var_export([
            'url' => $token->getUrl(),
            'token' => $token->getToken(),
            'project_id' => $project_id,
        ], true) . ";\n");

        if ($config_file_written) {
            $output->writeln("Settings written to <comment>$config_file_path</comment>.");
        } else {
            throw new RuntimeException("Failed to write config to '$config_file_written'");
        }
    }

    /**
     * @param  InputInterface $input
     * @return bool
     */
    protected function isDebug(InputInterface $input)
    {
        return $input->getOption('debug');
    }

    /**
     * Return config file path.
     *
     * @return string
     */
    protected function getConfigFilePath()
    {
        return dirname(__DIR__, 2) . '/config.php';
    }
}
