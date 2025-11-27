<?php

declare(strict_types=1);

namespace Application\Service;

use Doctrine\DBAL\Connection;
use Laminas\Session\Container;
use RuntimeException;

class AuthService
{
    private Connection $connection;
    private Container $session;

    public function __construct(Connection $connection, ?Container $session = null)
    {
        $this->connection = $connection;
        $this->session = $session ?: new Container('auth');
    }

    /**
     * Register a new user. Returns the new user id.
     * Throws RuntimeException on duplicate email.
     */
    public function register(string $name, string $email, string $password): int
    {
        $email = strtolower(trim($email));
        $existing = $this->connection->fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) {
            throw new RuntimeException('Email already registered');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->connection->insert('users', [
            'name' => $name,
            'email' => $email,
            'password_hash' => $hash,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int)$this->connection->lastInsertId();
    }

    /**
     * Authenticate using email + password. Returns true on success.
     */
    public function authenticate(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        $row = $this->connection->fetchAssociative('SELECT id, name, email, password_hash FROM users WHERE email = ?', [$email]);
        if (! $row) {
            return false;
        }

        if (! password_verify($password, $row['password_hash'])) {
            return false;
        }

        // Successful authentication: store identity in session (avoid storing password hash)
        $this->session->identity = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
        ];

        return true;
    }

    public function getIdentity(): ?array
    {
        return $this->session->offsetExists('identity') ? $this->session->identity : null;
    }

    public function clearIdentity(): void
    {
        if ($this->session->offsetExists('identity')) {
            unset($this->session->identity);
        }
    }
}
