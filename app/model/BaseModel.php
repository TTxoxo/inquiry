<?php

declare(strict_types=1);

namespace app\model;

use PDO;
use PDOException;
use RuntimeException;

abstract class BaseModel
{
    private static ?PDO $pdo = null;

    /**
     * @return list<string>
     */
    abstract public function fields(): array;

    abstract protected function tableName(): string;

    public function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = (string) env('DB_HOST', env('DATABASE_HOST', '127.0.0.1'));
        $port = (string) env('DB_PORT', env('DATABASE_PORT', '3306'));
        $database = (string) env('DB_DATABASE', env('DATABASE_NAME', 'inquiry'));
        $username = (string) env('DB_USERNAME', env('DATABASE_USER', 'root'));
        $password = (string) env('DB_PASSWORD', env('DATABASE_PASSWORD', ''));
        $charset = (string) env('DB_CHARSET', env('DATABASE_CHARSET', 'utf8mb4'));

        try {
            self::$pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset),
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed', 5001, $exception);
        }

        return self::$pdo;
    }

    public function table(string $name = ''): string
    {
        $table = $name === '' ? $this->tableName() : $name;

        return (string) env('DB_PREFIX', env('DATABASE_PREFIX', '')) . $table;
    }

    public function insert(array $data): int
    {
        $filtered = $this->filterData($data);
        $columns = array_keys($filtered);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
        $statement = $this->pdo()->prepare(
            sprintf(
                'INSERT INTO `%s` (%s) VALUES (%s)',
                $this->table(),
                implode(', ', array_map(static fn (string $column): string => sprintf('`%s`', $column), $columns)),
                implode(', ', $placeholders)
            )
        );
        $statement->execute($filtered);

        return (int) $this->pdo()->lastInsertId();
    }

    public function updateById(int $id, array $data): void
    {
        $filtered = $this->filterData($data);
        $assignments = implode(', ', array_map(static fn (string $column): string => sprintf('`%s` = :%s', $column, $column), array_keys($filtered)));
        $filtered['id'] = $id;
        $statement = $this->pdo()->prepare(sprintf('UPDATE `%s` SET %s WHERE `id` = :id', $this->table(), $assignments));
        $statement->execute($filtered);
    }

    public function findById(int $id): ?array
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findOneBy(array $criteria, string $orderBy = '`id` ASC'): ?array
    {
        [$where, $bindings] = $this->buildWhere($criteria);
        $statement = $this->pdo()->prepare(sprintf('SELECT %s FROM `%s`%s ORDER BY %s LIMIT 1', $this->columnList(), $this->table(), $where, $orderBy));
        $statement->execute($bindings);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findAllBy(array $criteria = [], string $orderBy = '`id` ASC', ?int $limit = null): array
    {
        [$where, $bindings] = $this->buildWhere($criteria);
        $sql = sprintf('SELECT %s FROM `%s`%s ORDER BY %s', $this->columnList(), $this->table(), $where, $orderBy);
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }

        $statement = $this->pdo()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countBy(array $criteria = []): int
    {
        [$where, $bindings] = $this->buildWhere($criteria);
        $statement = $this->pdo()->prepare(sprintf('SELECT COUNT(*) FROM `%s`%s', $this->table(), $where));
        $statement->execute($bindings);

        return (int) $statement->fetchColumn();
    }

    protected function filterData(array $data): array
    {
        $allowed = array_flip($this->fields());
        $filtered = [];
        foreach ($data as $key => $value) {
            if (isset($allowed[$key])) {
                $filtered[$key] = $value;
            }
        }

        if ($filtered === []) {
            throw new RuntimeException('No valid data for model write');
        }

        return $filtered;
    }

    private function columnList(): string
    {
        return implode(', ', array_map(static fn (string $field): string => sprintf('`%s`', $field), $this->fields()));
    }

    /**
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildWhere(array $criteria): array
    {
        if ($criteria === []) {
            return ['', []];
        }

        $clauses = [];
        $bindings = [];
        foreach ($criteria as $field => $value) {
            $param = 'w_' . $field;
            if ($value === null) {
                $clauses[] = sprintf('`%s` IS NULL', $field);
                continue;
            }

            if (is_array($value)) {
                $items = [];
                foreach (array_values($value) as $index => $item) {
                    $itemParam = sprintf('%s_%d', $param, $index);
                    $items[] = ':' . $itemParam;
                    $bindings[$itemParam] = $item;
                }
                $clauses[] = sprintf('`%s` IN (%s)', $field, implode(', ', $items));
                continue;
            }

            $clauses[] = sprintf('`%s` = :%s', $field, $param);
            $bindings[$param] = $value;
        }

        return [' WHERE ' . implode(' AND ', $clauses), $bindings];
    }
}
