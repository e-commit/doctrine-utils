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

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Psr\Log\LoggerInterface;

class Driver extends AbstractDriverMiddleware
{
    public function __construct(DriverInterface $driver, private LoggerInterface $logger)
    {
        parent::__construct($driver);
    }

    public function connect(#[\SensitiveParameter] array $params): DriverConnection
    {
        if (method_exists(AbstractMySQLPlatform::class, 'getColumnTypeSQLSnippets')) {
            // DBAL 3

            return new Connection3(
                parent::connect($params),
                $this->logger
            );
        }

        return new Connection4(
            parent::connect($params),
            $this->logger
        );
    }
}
