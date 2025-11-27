<?php

declare(strict_types=1);

namespace Application\Factory;

use Psr\Container\ContainerInterface;
use Application\Service\AuthService;
use Laminas\Session\Container;

class AuthServiceFactory
{
    public function __invoke(ContainerInterface $container): AuthService
    {
        $conn = $container->get('doctrine.connection');
        // Use a namespaced session container for auth
        $session = new Container('auth');

        return new AuthService($conn, $session);
    }
}
