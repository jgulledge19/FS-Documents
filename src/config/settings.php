<?php

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

try {
    /** @var Dotenv $dotenv */
    $dotenv = new Dotenv(dirname(dirname(__DIR__)));
    $dotenv->load();
} catch (InvalidPathException $e) {
    echo 'Invalid .env file '.$e->getMessage();
    exit();
}

if ((bool)$_ENV['DISPLAY_ERRORS']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}


/** @var array $settings */
$settings = [
    'settings' => [
        'displayErrorDetails' => (bool)$_ENV['DISPLAY_ERRORS'],
        'addContentLengthHeader' => false
    ]
];
