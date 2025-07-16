<?php

declare(strict_types=1);

namespace Lib\Prisma\Classes;

use PDO;
use PDOStatement;
use PDOException;
use Exception;

/**
 * @property Todo $todo
 */
final class Prisma
{
    private static ?Prisma $instance = null;
    private PDO $_pdo;
    private array $_models = [];
    
    /**
     * Private constructor to prevent direct instantiation.
     * Use Prisma::getInstance() to get the singleton instance.
     */
    private function __construct()
    {
        $this->_pdo = DatabaseConnection::getInstance()->getConnection();
    }

    /**
     * Singleton method to get the instance.
     * 
     * @return Prisma The Prisma instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Magic method to lazily initialize models.
     *
     * This method checks if a requested model is already instantiated.
     * If not, it instantiates the model and assigns it to the corresponding property.
     *
     * @param string $name The name of the property being accessed.
     * @return mixed The instance of the requested model.
     * @throws Exception Throws an exception if the class does not exist.
     */
    public function __get($name)
    {
        if (!isset($this->_models[$name])) {
            $className = ucfirst($name);
            $fullyQualifiedName = __NAMESPACE__ . "\\" . $className;
            if (class_exists($fullyQualifiedName)) {
                $this->_models[$name] = new $fullyQualifiedName($this->_pdo);
            } else {
                throw new Exception("Class $fullyQualifiedName not found.");
            }
        }
        return $this->_models[$name];
    }

    /**
     * Executes a raw SQL command that does not return a result set.
     * 
     * This method is suitable for SQL statements like INSERT, UPDATE, DELETE.
     * It returns the number of rows affected by the SQL command.
     *
     * @param string $sql The raw SQL command to be executed.
     * @return int The number of rows affected.
     * @throws Exception Throws an exception if the database operation fails.
     */
    public function executeRaw(string $sql): int
    {
        try {
            $affectedRows = $this->_pdo->exec($sql);
            return $affectedRows;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Executes a raw SQL query and returns the result set.
     * 
     * This method is suitable for SELECT queries or when expecting a return value.
     * It returns an array containing all of the result set rows.
     *
     * @param string $sql The raw SQL query to be executed.
     * @return array The result set as an array.
     * @throws Exception Throws an exception if the database operation fails.
     */
    public function queryRaw(string $sql): array
    {
        try {
            $stmt = $this->_pdo->query($sql);
            if ($stmt === false) {
                throw new Exception("Failed to execute query: $sql");
            }
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Executes a set of operations within a database transaction.
     *
     * This method accepts an array of callable functions, each representing a database operation.
     * These operations are executed within a single database transaction. If any operation fails,
     * the entire transaction is rolled back. If all operations succeed, the transaction is committed.
     *
     * @param callable[] $operations An array of callable functions for transactional execution.
     * @return void
     * @throws Exception Throws an exception if the transaction fails.
     *
     * Example Usage:
     * $prisma = Prisma::getInstance();
     * $prisma->transaction([
     *     function() use ($prisma) { $prisma->UserModel->create(['name' => 'John Doe']); },
     *     function() use ($prisma) { $prisma->OrderModel->create(['userId' => 1, 'product' => 'Book']); }
     * ]);
     */
    public function transaction(array $operations): void
    {
        try {
            $this->_pdo->beginTransaction();

            foreach ($operations as $operation) {
                call_user_func($operation);
            }
            $this->_pdo->commit();
        } catch (Exception $e) {
            $this->_pdo->rollBack();
            throw $e;
        }
    }
}

final class DatabaseConnection
{
    private static ?DatabaseConnection $instance = null;
    private ?PDO $_pdo = null;

    private function __construct()
    {
        $this->initializePDO();
    }

    public static function getInstance(): DatabaseConnection
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        if ($this->_pdo === null) {
            $this->initializePDO();
        }
        return $this->_pdo;
    }

    private function initializePDO()
    {
        if ($this->_pdo === null) {
            $databaseUrl = $_ENV['DATABASE_URL'];
            if (!$databaseUrl) {
                throw new Exception('DATABASE_URL not set in .env file.');
            }

            $parsedUrl = parse_url($databaseUrl);
            $dbProvider = strtolower($parsedUrl['scheme'] ?? '');

            if ($dbProvider === 'file' || $dbProvider === 'sqlite') {
                $dbRelativePath = ltrim($parsedUrl['path'], '/');
                $dbRelativePath = str_replace('/', DIRECTORY_SEPARATOR, $dbRelativePath);
                $prismaDirectory = DOCUMENT_PATH . DIRECTORY_SEPARATOR . 'prisma';
                $potentialAbsolutePath = realpath($prismaDirectory . DIRECTORY_SEPARATOR . $dbRelativePath);
                $absolutePath = $potentialAbsolutePath ?: $prismaDirectory . DIRECTORY_SEPARATOR . $dbRelativePath;

                if (!file_exists($absolutePath)) {
                    throw new Exception("SQLite database file not found or unable to create: " . $absolutePath);
                }

                $dsn = "sqlite:" . $absolutePath;
            } else {
                $pattern = '/:\/\/(.*?):(.*?)@/';
                preg_match($pattern, $databaseUrl, $matches);
                $dbUser = $matches[1] ?? '';
                $dbPassword = $matches[2] ?? '';
                $databaseUrlWithoutCredentials = preg_replace($pattern, '://', $databaseUrl);
                $parsedUrl = parse_url($databaseUrlWithoutCredentials);
                $dbProvider = strtolower($parsedUrl['scheme'] ?? '');
                $dbName = isset($parsedUrl['path']) ? substr($parsedUrl['path'], 1) : '';
                $dbHost = $parsedUrl['host'] ?? '';
                $dbPort = $parsedUrl['port'] ?? ($dbProvider === 'mysql' ? 3306 : 5432);
                if ($dbProvider === 'mysql') {
                    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8";
                } elseif ($dbProvider === 'postgresql') {
                    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName";
                } else {
                    throw new Exception("Unsupported database provider: $dbProvider");
                }
            }
            try {
                $this->_pdo = new ExtendedPDO($dsn, $dbUser ?? null, $dbPassword ?? null);
                $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->_pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                throw new Exception("Connection error: " . $e->getMessage());
            }
        }
    }
}

class ExtendedPDO extends PDO
{
    private int $transactionDepth = 0;
    private bool $rollbackRequested = false;

    private array $activeStatements = [];

    public function beginTransaction(): bool
    {
        if ($this->transactionDepth == 0) {
            parent::beginTransaction();
        } else {
            $this->finalizeStatements();
            $this->exec("SAVEPOINT trans{$this->transactionDepth}");
        }
        $this->transactionDepth++;
        return true;
    }

    public function commit(): bool
    {
        if ($this->transactionDepth <= 0) {
            return true;
        }

        $this->transactionDepth--;

        if ($this->transactionDepth == 0) {
            if ($this->rollbackRequested) {
                parent::rollBack();
            } else {
                $this->finalizeStatements();
                parent::commit();
            }
            $this->resetTransactionState();
        } else {
            if ($this->rollbackRequested) {
                $this->exec("ROLLBACK TO SAVEPOINT trans{$this->transactionDepth}");
            } else {
                $this->finalizeStatements();
                $this->exec("RELEASE SAVEPOINT trans{$this->transactionDepth}");
            }
        }

        return true;
    }

    public function rollBack(): bool
    {
        if ($this->transactionDepth <= 0) {
            return true;
        }

        $this->transactionDepth--;

        if ($this->transactionDepth == 0) {
            parent::rollBack();
            $this->resetTransactionState();
        } else {
            $this->exec("ROLLBACK TO SAVEPOINT trans{$this->transactionDepth}");
            $this->rollbackRequested = true;
        }

        return true;
    }

    private function resetTransactionState(): void
    {
        $this->transactionDepth = 0;
        $this->rollbackRequested = false;
    }

    public function isTransactionActive(): bool
    {
        return $this->transactionDepth > 0;
    }

    public function prepare(string $statement, array $driver_options = []): PDOStatement|false
    {
        $stmt = parent::prepare($statement, $driver_options);
        if ($stmt !== false) {
            $this->activeStatements[] = $stmt;
        }
        return $stmt;
    }

    private function finalizeStatements(): void
    {
        foreach ($this->activeStatements as $stmt) {
            $stmt->closeCursor();
        }
        $this->activeStatements = [];
    }
}