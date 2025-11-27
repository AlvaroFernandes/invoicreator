<?php

declare(strict_types=1);

namespace Application\Factory;

use Psr\Container\ContainerInterface;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;

class ConnectionFactory
{
    public function __invoke(ContainerInterface $container): Connection
    {
        $config = $container->has('config') ? $container->get('config') : [];

        $params = [];
        if (isset($config['doctrine']['connection']['default'])) {
            $params = $config['doctrine']['connection']['default'];
        }

        // Fallback minimal connection params
        $params += [
            'driver' => 'pdo_mysql',
            'host'   => '127.0.0.1',
            'port'   => 3306,
            'user'   => 'root',
            'password' => '',
            'dbname' => '',
            'charset' => 'utf8mb4',
        ];

        return DriverManager::getConnection($params);
    }
}
