<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'login' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/login',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'login',
                    ],
                ],
            ],
            'register' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/register',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'register',
                    ],
                ],
            ],
            'logout' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/logout',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'logout',
                    ],
                ],
            ],
            'dashboard' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/dashboard',
                    'defaults' => [
                        'controller' => Controller\DashboardController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
            Controller\AuthController::class  => InvokableFactory::class,
            Controller\DashboardController::class => InvokableFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            // Doctrine DBAL connection service
            'doctrine.connection' => \Application\Factory\ConnectionFactory::class,
            // Auth service
            \Application\Service\AuthService::class => \Application\Factory\AuthServiceFactory::class,
            'Application\AuthService' => \Application\Factory\AuthServiceFactory::class,
            // Provide Laminas AuthenticationServiceInterface compatibility
            'AuthenticationService' => function ($container) {
                $sm = $container;
                $auth = $sm->has(\Application\Service\AuthService::class)
                    ? $sm->get(\Application\Service\AuthService::class)
                    : ($sm->has('Application\\AuthService') ? $sm->get('Application\\AuthService') : null);

                return new \Application\Auth\AuthenticationServiceAdapter($auth);
            },
            \Laminas\Authentication\AuthenticationServiceInterface::class => function ($container) {
                return $container->get('AuthenticationService');
            },
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'identity' => function ($container) {
                $sm = $container;
                $auth = $sm->has(\Application\Service\AuthService::class)
                    ? $sm->get(\Application\Service\AuthService::class)
                    : ($sm->has('Application\\AuthService') ? $sm->get('Application\\AuthService') : null);

                $getIdentity = function () use ($auth) {
                    if (! $auth) {
                        return null;
                    }
                    return $auth->getIdentity();
                };

                return new \Application\View\Helper\Identity($getIdentity);
            },
            'flash' => function ($container) {
                return new \Application\View\Helper\Flash();
            },
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
