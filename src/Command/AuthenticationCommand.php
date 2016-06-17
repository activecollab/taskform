<?php

/*
 * This library is free software, and it is part of the Active Collab TaskForm project. Check LICENSE for details.
 *
 * (c) A51 doo <info@activecollab.com>
 */

namespace ActiveCollab\TaskForm\Command;

use ActiveCollab\SDK\ClientInterface;
use ActiveCollab\SDK\ResponseInterface;
use ActiveCollab\SDK\TokenInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @package ActiveCollab\TaskForm\Command
 */
abstract class AuthenticationCommand extends Command
{
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
     * Ask user to pick an account that they want to authenticate with.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @param  ClientInterface $client
     * @return int
     */
    protected function getProjectId(InputInterface $input, OutputInterface $output, ClientInterface $client)
    {
        /** @var ResponseInterface $projects_reponse */
        $projects_reponse = $client->get('projects/names');

        if ($projects_reponse->isJson()) {
            $project_id_names_map = $projects_reponse->getJson();
        } else {
            throw new RuntimeException('Invalid project names response');
        }

        if (empty($project_id_names_map)) {
            throw new \RuntimeException("You don't have access to any of the projects in that Active Collab");
        }

        $output->writeln('Here is a list of active projects that you have access to:');
        $output->writeln('');

        foreach ($project_id_names_map as $id => $name) {
            $output->writeln("    <comment>*</comment> {$name} <comment>#{$id}</comment>");
        }

        $output->writeln('');

        $project_id = (int) $this->getHelper('question')->ask($input, $output, (new Question("Which one would you like to use? Please enter project #:\n")));

        if ($project_id) {
            if (array_key_exists($project_id, $project_id_names_map)) {
                return $project_id;
            } else {
                throw new \RuntimeException("You don't have access to project #{$project_id}");
            }
        } else {
            throw new \RuntimeException('Account ID is not set');
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
        $config_file_path = dirname(__DIR__, 2) . '/config.php';

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
}
