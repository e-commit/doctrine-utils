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

namespace Ecommit\DoctrineUtils\Tests;

use Doctrine\DBAL\Query\QueryBuilder as QueryBuilderDBAL;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder as QueryBuilderORM;
use Ecommit\DoctrineUtils\Tests\App\Doctrine;
use Ecommit\DoctrineUtils\Tests\App\Entity\Entity;
use Ecommit\DoctrineUtils\Tests\App\SqlLogger;
use PHPUnit\Framework\TestCase;

abstract class AbstractTest extends TestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var SqlLogger
     */
    protected $sqlLogger;

    protected $queryBuilder;

    protected function setUp(): void
    {
        $this->em = Doctrine::getEntityManager();
        $this->sqlLogger = $this->em->getConnection()->getConfiguration()->getSQLLogger();
    }

    protected function tearDown(): void
    {
        $this->em->clear();
        $this->sqlLogger->reset();
        $this->queryBuilder = null;
    }

    protected function createQueryBuilderDBAL(): QueryBuilderDBAL
    {
        return $this->em->getConnection()->createQueryBuilder();
    }

    protected function createDefaultQueryBuilderDBAL(): QueryBuilderDBAL
    {
        $queryBuilder = $this->createQueryBuilderDBAL();
        $queryBuilder->select('e.*')
            ->from('entity', 'e')
            ->andWhere('e.entity_id <= :id')
            ->setParameter('id', 52)
            ->orderBy('e.entity_id', 'ASC');

        return $queryBuilder;
    }

    protected function createQueryBuilderORM(string $repository, string $alias): QueryBuilderORM
    {
        return $this->em->getRepository($repository)->createQueryBuilder($alias);
    }

    protected function createDefaultQueryBuilderORM(): QueryBuilderORM
    {
        $queryBuilder = $this->createQueryBuilderORM(Entity::class, 'e');
        $queryBuilder->select('e')
            ->andWhere('e.entityId <= :id')
            ->setParameter('id', 52)
            ->orderBy('e.entityId', 'ASC');

        return $queryBuilder;
    }

    protected function checkEntityIds($result, $expectedIds): void
    {
        $ids = [];
        foreach ($result as $entity) {
            if ($entity instanceof Entity) {
                $ids[] = $entity->getEntityId();
            } elseif (\is_array($entity)) {
                $ids[] = $entity['entity_id'];
            } else {
                throw new \Exception('Non géré');
            }
        }

        $this->assertEquals($expectedIds, $ids);
    }

    protected function saveQueryBuilder($queryBuilder): void
    {
        $this->queryBuilder = clone $queryBuilder;
    }

    protected function checkIfQueryBuildNotChange($queryBuilder): void
    {
        $this->assertEquals($this->queryBuilder, $queryBuilder);
    }
}
