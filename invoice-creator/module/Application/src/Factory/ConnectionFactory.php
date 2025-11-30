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

        // Support common doctrine config shapes used in laminas skeletons:
        // 1) ['doctrine']['connection']['default'] => DBAL params
        // 2) ['doctrine']['connection']['orm_default']['params'] => orm params
        if (isset($config['doctrine']['connection']['default'])) {
            $params = $config['doctrine']['connection']['default'];
        } elseif (isset($config['doctrine']['connection']['orm_default']['params'])) {
            $params = $config['doctrine']['connection']['orm_default']['params'];
        }

        // If the config uses nested 'params' (some dist files do), normalize it
        if (isset($params['params']) && is_array($params['params'])) {
            $params = $params['params'];
        }

        // Map common keys to DBAL expected keys and provide sensible defaults
        $dbParams = [
            'driver'   => $params['driver'] ?? ($params['driverClass'] ?? 'pdo_mysql'),
            'host'     => $params['host'] ?? '127.0.0.1',
            'port'     => $params['port'] ?? 3306,
            'user'     => $params['user'] ?? $params['username'] ?? 'root',
            'password' => $params['password'] ?? '',
            'dbname'   => $params['dbname'] ?? $params['database'] ?? '',
            'charset'  => $params['charset'] ?? 'utf8mb4',
        ];

        return DriverManager::getConnection($dbParams);
    }
}
