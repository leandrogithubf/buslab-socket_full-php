<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/sociallydev/spaces-api/spaces.php';

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\Setup;
use Symfony\Component\Dotenv\Dotenv;

// Arquivo de configuarações
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Configurações de base de dados MySQL
AnnotationRegistry::registerLoader('class_exists');

$config = Setup::createConfiguration(($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV']) === 'dev');

$config->setNamingStrategy(new \Doctrine\ORM\Mapping\UnderscoreNamingStrategy(CASE_UPPER));
$config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), [
    __DIR__ . '/src/Entity/',
]));

$em = EntityManager::create([
    'driver' => 'pdo_mysql',
    'host' => $_ENV['DATABASE_HOST'] ?? $_SERVER['DATABASE_HOST'],
    'port' => $_ENV['DATABASE_PORT'] ?? $_SERVER['DATABASE_PORT'],
    'user' => $_ENV['DATABASE_USER']  ?? $_SERVER['DATABASE_USER'],
    'password' => $_ENV['DATABASE_PASSWORD']  ?? $_SERVER['DATABASE_PASSWORD'],
    'dbname' => $_ENV['DATABASE_NAME']  ?? $_SERVER['DATABASE_NAME'],
], $config);

// Conigurações do Redis
$redis = new Predis\Client([
    'scheme' => $_ENV['REDIS_SCHEME'] ?? $_SERVER['REDIS_SCHEME'],
    'host' => $_ENV['REDIS_HOST'] ?? $_SERVER['REDIS_HOST'],
    'username' => $_ENV['REDIS_USERNAME'] ?? $_SERVER['REDIS_USERNAME'],
    'password' => $_ENV['REDIS_PASSWORD'] ?? $_SERVER['REDIS_PASSWORD'],
    'port' => $_ENV['REDIS_PORT'] ?? $_SERVER['REDIS_PORT'],
    'read_write_timeout' => -1,
]);

$redisPublisher = new Predis\Client([
    'scheme' => $_ENV['REDIS_SCHEME'] ?? $_SERVER['REDIS_SCHEME'],
    'host' => $_ENV['REDIS_HOST'] ?? $_SERVER['REDIS_HOST'],
    'username' => $_ENV['REDIS_USERNAME'] ?? $_SERVER['REDIS_USERNAME'],
    'password' => $_ENV['REDIS_PASSWORD'] ?? $_SERVER['REDIS_PASSWORD'],
    'port' => $_ENV['REDIS_PORT'] ?? $_SERVER['REDIS_PORT'],
    'read_write_timeout' => -1,
]);

$directories = [
    'obd_raw_data' => __DIR__ . '/var/obd-log',
];
