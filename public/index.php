<?php


declare(strict_types=1);

error_reporting(E_ALL ^ E_DEPRECATED); // Deprecated off
ini_set('display_errors', 1);

use Mongolid\Connection\Connection;
use Mongolid\Connection\Manager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteRunner;

require __DIR__ . '/../vendor/autoload.php';

$_ENV = parse_ini_file("../.env");

$Connection = new Connection($_ENV["MONGO_CONNECT"]);
$Connection->defaultDatabase = $_ENV["DB_NAME"];

$manager = new Manager();
$manager->setConnection($Connection);

$app = AppFactory::create();

$app->add(function (Request $request, RouteRunner $handler) use ($app) {

    $response = $handler->handle($request);

    return $response->withHeader("Content-Type", "application/json");

});

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$errorMiddleware->setDefaultErrorHandler(function (
    ServerRequestInterface $request,
    Throwable              $exception,
    bool                   $displayErrorDetails,
    bool                   $logErrors,
    bool                   $logErrorDetails,
    ?LoggerInterface       $logger = null
) use ($app) {

    if ($logger) {
        $logger->error($exception->getMessage());
    }

    $payload = ['error' => $exception->getMessage()];

    $response = $app->getResponseFactory()->createResponse();

    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));

    return $response->withHeader("Content-Type", "application/json")->withStatus(400);

});

$app->get("/git/pull", function (Request $request, Response $response, $args) {

    return $response;

});

$app->run();