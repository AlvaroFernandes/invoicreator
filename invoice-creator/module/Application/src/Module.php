<?php

declare(strict_types=1);

namespace Application;

use Laminas\Mvc\MvcEvent;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\SessionManager;
use Laminas\Session\Container;

class Module
{
    public function onBootstrap(MvcEvent $e): void
    {
        $application = $e->getApplication();
        $request = $application->getRequest();

        // Determine whether the current request is secure (HTTPS)
        $isSecure = false;
        try {
            $uri = $request->getUri();
            $isSecure = (method_exists($uri, 'getScheme') && $uri->getScheme() === 'https');
        } catch (\Throwable $ex) {
            // If we can't determine, default to non-secure. This is safe for local dev.
            $isSecure = false;
        }

        $config = new SessionConfig();
        $config->setOptions([
            'name' => 'invoice_session',
            // Session cookie lasts until the browser is closed
            'cookie_lifetime' => 0,
            // Garbage collection max lifetime (seconds) - 30 days
            'gc_maxlifetime' => 60 * 60 * 24 * 30,
            'cookie_secure' => $isSecure,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);

        $manager = new SessionManager($config);
        Container::setDefaultManager($manager);

        // Start the session manager. This will ensure session is available for AuthService.
        try {
            $manager->start();
        } catch (\Throwable $ex) {
            // Starting the session can fail in some environments; do not break the application.
        }
    }

    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}
