<?php

/*
 * This library is free software, and it is part of the Active Collab TaskForm project. Check LICENSE for details.
 *
 * (c) A51 doo <info@activecollab.com>
 */

namespace ActiveCollab\TaskForm;

use ActiveCollab\SDK\Client;
use ActiveCollab\SDK\Exceptions\AppException;
use ActiveCollab\SDK\Token;
use Exception;

require_once 'vendor/autoload.php';

/**
 * Equivalent to htmlspecialchars(), but allows &#[0-9]+ (for unicode).
 *
 * @param  string $str
 * @return string
 */
function clean($str)
{
    if (is_scalar($str)) {
        $str = preg_replace('/&(?!#(?:[0-9]+|x[0-9A-F]+);?)/si', '&amp;', $str);
        $str = str_replace(['<', '>', '"'], ['&lt;', '&gt;', '&quot;'], $str);

        return $str;
    } else {
        return '';
    }
}

/**
 * Render an error message.
 *
 * @param string               $message
 * @param int                  $code
 * @param string[]|string|null $reason
 */
function http_error($message, $code, $reason = null)
{
    header($_SERVER['SERVER_PROTOCOL'] . " $code $message", true, $code);
    print '<h1>' . clean($message) . '</h1>';

    if (!empty($reason)) {
        if (is_scalar($reason)) {
            print '<p>' . clean($reason) . '</p>';
        } elseif (is_array($reason)) {
            print '<p>Form submission failed because:</p>';
            print '<ul>';

            foreach ($reason as $reason_line) {
                print '<li>' . clean($reason_line) . '</li>';
            }

            print '</ul>';
        }
    }

    die();
}

$config = [];

if (is_file(__DIR__ . '/config.php')) {
    $config = require 'config.php';

    foreach (['token', 'url', 'project_id'] as $required_config_option) {
        if (empty($config[$required_config_option])) {
            http_error('Internal Server Error', 500, "Required config option $required_config_option not found in configuration file.");
        }
    }
} else {
    http_error('Internal Server Error', 500, 'Task form is not connected to ActiveCollab. Please run connect.php script using command line to make a connection.');
}

if (empty($_POST)) {
    http_error('Bad Request', 400, 'POST data expected.');
}

if (array_key_exists('name', $_POST)) {
    $name = trim($_POST['name']);
    unset($_POST['name']);
} else {
    $name = '';
}

if (array_key_exists('body', $_POST)) {
    $body = trim($_POST['body']);
    unset($_POST['body']);
} else {
    $body = '';
}

if (array_key_exists('submitted_by_name', $_POST)) {
    $submitted_by_name = trim($_POST['submitted_by_name']);
    unset($_POST['submitted_by_name']);
} else {
    $submitted_by_name = '';
}

if (array_key_exists('submitted_by_email', $_POST)) {
    $submitted_by_email = trim($_POST['submitted_by_email']);
    unset($_POST['submitted_by_email']);
} else {
    $submitted_by_email = '';
}

$validation_errors = [];

foreach (['name' => 'Task name', 'submitted_by_name' => 'Your name', 'submitted_by_email' => 'Your email address'] as $required_field_name => $verbose_required_field_name) {
    if (empty($$required_field_name)) {
        $validation_errors[] = "$verbose_required_field_name is required";
    }
}

if ($submitted_by_email && !filter_var($submitted_by_email, FILTER_VALIDATE_EMAIL)) {
    $validation_errors[] = 'Your email address is not valid';
}

if (!empty($validation_errors)) {
    http_error('Bad Request', 400, $validation_errors);
}

$additional_body_lines = [];

foreach ($_POST as $k => $v) {
    $attribute_name = ucfirst(str_replace(['_', '-'], [' ', ' '], $k));
    $attribute_value = trim(is_array($v) ? implode(', ', $v) : $v);

    if (empty($attribute_value) && (is_string($attribute_value) || $attribute_value === null)) {
        $attribute_value = '--';
    }

    $body_line = '<strong>' . clean($attribute_name) . ':</strong>';

    if (nl2br($attribute_value) != $attribute_value) {
        $body_line .= '<br>' . nl2br(clean($attribute_value));
    } else {
        $body_line .= ' ' . clean($attribute_value);
    }

    $additional_body_lines[] = $body_line;
}

// Clean up body, if it is present
if ($body) {
    $body = nl2br(clean($body));
}

// We have both body and additional body lines
if (!empty($body) && !empty($additional_body_lines)) {
    $body = $body . '<br><br><br>' . implode('<br><br>', $additional_body_lines);

// We have only additional body lines
} elseif (empty($body) && !empty($additional_body_lines)) {
    $body = implode('<br><br>', $additional_body_lines);
}

$token = new Token($config['token'], $config['url']);
$client = new Client($token);

try {
    $result = $client->post(
        "projects/{$config['project_id']}/tasks",
        [
            'name' => $name,
            'body' => $body,
            'created_by_id' => 0,
            'created_by_name' => $submitted_by_name,
            'created_by_email' => $submitted_by_email,
        ]
    );

    if ($result->isJson()) {
        $result_json = $result->getJson();

        if (!empty($result_json['single']['id'])) {
            print '<h1>Thank you</h1>';
            print sprintf('Request #%d has been submitted.', $result_json['single']['id']);
        } else {
            http_error('Failed to submit your request.', 500);
        }
    }
} catch (Exception $e) {
    http_error($e->getMessage(), 500);
}
