<?php

declare(strict_types=1);

/*
 * This file is part of the ecommit/doctrine-utils package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\DoctrineUtils\Tests\App;

use Doctrine\DBAL\Logging\SQLLogger as BaseSQLLogger;
use Doctrine\DBAL\Types\Type;

class SqlLogger implements BaseSQLLogger
{
    /**
     * @var array<int, array{
     *     sql: string,
     *     params: list<mixed>|array<string, mixed>,
     *     types: array<int, Type|int|string|null>|array<string, Type|int|string|null>|null
     * }>
     */
    public $queries = [];

    /** @var int */
    public $currentQuery = 0;

    public function startQuery($sql, array $params = null, array $types = null): void
    {
        if (null === $params) {
            $params = [];
        }

        $this->queries[++$this->currentQuery] = [
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
        ];
    }

    public function stopQuery(): void
    {
    }

    public function reset(): void
    {
        $this->queries = [];
        $this->currentQuery = 0;
    }
}
