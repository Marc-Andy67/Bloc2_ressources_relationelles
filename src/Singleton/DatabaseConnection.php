<?php

namespace App\Singleton;

/**
 * Pedagogical Example of a Singleton Pattern in PHP.
 * In Symfony, the Dependency Injection Container (Service Container) 
 * inherently provides services as singletons.
 * However, this class mathematically guarantees only ONE PDO connection instance is ever spawned manually.
 */
class DatabaseConnection
{
    private static ?\PDO $instance = null;

    // The constructor is private to prevent initiation with outer code.
    private function __construct()
    {
        // Example DSN, normally you'd read from $_ENV here 
        // string $dsn, string $username, string $password
    }

    // The object is created from within the class itself
    // only if the class has no instance.
    public static function getInstance(): \PDO
    {
        if (self::$instance === null) {
            $dsn = $_ENV['DATABASE_DSN'] ?? 'mysql:host=127.0.0.1;dbname=app';
            $user = $_ENV['DATABASE_USER'] ?? 'root';
            $pass = $_ENV['DATABASE_PASSWORD'] ?? '';

            try {
                self::$instance = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]);
            } catch (\PDOException $e) {
                throw new \RuntimeException("Database Connection Error: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    // Prevent instance from being cloned (which would create a second instance of it)
    private function __clone()
    {
    }

    // Prevent from being unserialized (which would create a second instance of it)
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}
