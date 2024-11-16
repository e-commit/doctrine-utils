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

namespace Ecommit\DoctrineUtils\Tests\App\Logging;

use Doctrine\DBAL\Types\Type;
use Psr\Log\AbstractLogger;

class SqlLogger extends AbstractLogger
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

    public function log($level, $message, array $context = []): void
    {
        $params = [];
        $types = [];
        if (\array_key_exists('params', $context)) {
            $params = $context['params'];
        }
        if (\array_key_exists('types', $context)) {
            $types = $context['types'];
        }

        $this->queries[++$this->currentQuery] = [
            'sql' => (string) $message,
            'params' => $params,
            'types' => $types,
        ];
    }

    public function reset(): void
    {
        $this->queries = [];
        $this->currentQuery = 0;
    }
}
