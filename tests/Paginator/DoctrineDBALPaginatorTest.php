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

namespace Ecommit\DoctrineUtils\Tests\Paginator;

use Doctrine\DBAL\Query\QueryBuilder as QueryBuilderDBAL;
use Ecommit\DoctrineUtils\Paginator\AbstractDoctrinePaginator;
use Ecommit\DoctrineUtils\Paginator\DoctrineDBALPaginator;

/**
 * @phpstan-import-type PaginatorOptions from DoctrineDBALPaginator
 *
 * @template-extends AbstractDoctrinePaginatorTest<QueryBuilderDBAL, DoctrineDBALPaginator, mixed, mixed, PaginatorOptions>
 */
class DoctrineDBALPaginatorTest extends AbstractDoctrinePaginatorTest
{
    public function testWithByIdentifierOption(): void
    {
        $queryBuilder = $this->getDefaultQueryBuilder();
        $this->saveQueryBuilder($queryBuilder);

        $options = $this->getDefaultOptions(2, 5, $queryBuilder);
        $options['by_identifier'] = 'e.entity_id';

        $paginator = $this->createPaginator($options);

        $this->assertSame(3, $this->sqlLogger->currentQuery);
        $this->assertCount(5, $this->sqlLogger->queries[3]['params']);
        $this->assertEquals(range(6, 10), \array_slice($this->sqlLogger->queries[3]['params'], 0, 5));
        $this->assertCount(52, $paginator);
        $this->checkEntityIds($paginator, range(6, 10));
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    protected function getDefaultQueryBuilder(): mixed
    {
        return $this->createDefaultQueryBuilderDBAL();
    }

    protected function createPaginator(array $options): AbstractDoctrinePaginator
    {
        return new DoctrineDBALPaginator($options);
    }
}
