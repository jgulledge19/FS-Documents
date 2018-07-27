<?php
use Slim\App;
use Slim\Middleware\HttpBasicAuthentication;

require dirname(__DIR__).'/vendor/autoload.php';

/** @var array $settings ~ overridden in settings.php */
$settings = [];
require_once dirname(__DIR__) . '/src/config/settings.php';

/** @var App $app */
$app = new App($settings);

$app->add(new HttpBasicAuthentication([
    "callback" => function ($request, $response, $arguments) {
        //print_r($arguments);exit();
    },
    "error" => function ($request, \Slim\Http\Response $response, $arguments) {
        $data = [];
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response->write(json_encode($data, JSON_UNESCAPED_SLASHES));
    },
    "secure" => false,// to run locally on IPs
    "users" => [
        "user1" => "t00r",
        "user2" => "t00r"//"passw0rd"
    ]
]));

// Set up dependencies
require_once dirname(__DIR__) . '/src/config/dependencies.php';

// Register routes
require_once dirname(__DIR__) . '/src/config/routes.php';

try {
    $app->run();
} catch (\Slim\Exception\MethodNotAllowedException $exception) {
    echo $exception->getMessage();

} catch (\Slim\Exception\NotFoundException $exception) {
    echo $exception->getMessage();

} catch (\Exception $exception) {
    echo $exception->getMessage();
}