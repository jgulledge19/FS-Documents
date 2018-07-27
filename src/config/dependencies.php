<?php

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Slim\PDO\Database;

/** @var \Slim\Container $container */
$container = $app->getContainer();

// get PDO:
$container['pdo'] = new Database($_ENV['PDO_DSN'], $_ENV['PDO_USER'], $_ENV['PDO_USER']);
