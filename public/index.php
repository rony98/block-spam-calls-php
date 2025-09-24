<?php
declare(strict_types=1);

use BlockSpamCallsPhp\SpamCallChecker;
use DI\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Twilio\Rest\Client as TwilioClient;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();
$dotenv->required([
    'TWILIO_ACCOUNT_SID',
    'TWILIO_AUTH_TOKEN',
])->notEmpty();

$container = new Container();
AppFactory::setContainer($container);

$container->set(
    TwilioClient::class,
    fn (): TwilioClient => new TwilioClient(
        $_SERVER['TWILIO_ACCOUNT_SID'],
        $_SERVER['TWILIO_AUTH_TOKEN'],
    )
);

$container->set(
    LoggerInterface::class,
    fn (): LoggerInterface => (new Logger('name'))->pushHandler(
        new StreamHandler(__DIR__ . '/../app.log', Level::Debug)
    )
);

$container->set(
    SpamCallChecker::class,
    fn(ContainerInterface $container) => new SpamCallChecker($container->get(LoggerInterface::class))
);

$app = AppFactory::create();

$app->post('/', SpamCallChecker::class);

$app->get('/health', function ($request, $response, $args) {
    $healthData = [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'service' => 'spam-detection',
        'version' => '1.0.0'
    ];

    return $response->withJson($healthData);
});

$app->run();