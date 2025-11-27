<?php

declare(strict_types=1);

namespace Application\Auth;

use Application\Service\AuthService;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result;

/**
 * Minimal adapter that exposes a Laminas AuthenticationServiceInterface
 * backed by our Application\Service\AuthService.
 *
 * Only `hasIdentity`, `getIdentity`, and `clearIdentity` are delegated.
 * `authenticate` is implemented to support adapters that implement
 * a simple credential-based interface (optional). If not supported,
 * authentication will return FAILURE.
 */
class AuthenticationServiceAdapter implements AuthenticationServiceInterface
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function authenticate(?AdapterInterface $adapter = null): Result
    {
        // If adapter provides a simple authenticate method that returns bool,
        // call it. Otherwise, return a failure result.
        if (method_exists($adapter, 'authenticate')) {
            $res = $adapter->authenticate();
            // If adapter returns a Laminas\Authentication\Result, return it.
            if ($res instanceof Result) {
                return $res;
            }
            // If adapter returns truthy, report success and attach identity.
            if ($res) {
                return new Result(Result::SUCCESS, $this->getIdentity());
            }
        }

        return new Result(Result::FAILURE, null, ['No adapter authentication performed']);
    }

    public function hasIdentity(): bool
    {
        return (bool)$this->authService->getIdentity();
    }

    public function getIdentity()
    {
        return $this->authService->getIdentity();
    }

    public function clearIdentity(): void
    {
        $this->authService->clearIdentity();
    }
}
