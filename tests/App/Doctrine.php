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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Ecommit\DoctrineUtils\Tests\App\Entity\Entity;
use Ecommit\DoctrineUtils\Tests\App\Entity\Relation;

class Doctrine
{
    /**
     * @var EntityManagerInterface
     */
    protected static $entityManager;

    public static function getEntityManager(): EntityManagerInterface
    {
        if (static::$entityManager) {
            return static::$entityManager;
        }

        $config = Setup::createAnnotationMetadataConfiguration([__DIR__.'/Entity'], true, null, null, false);
        $config->setSQLLogger(new SqlLogger());
        static::$entityManager = EntityManager::create(
            [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            $config
        );

        return static::$entityManager;
    }

    public static function createSchema(): void
    {
        $entityManager = self::getEntityManager();

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema(
            $entityManager->getMetadataFactory()->getAllMetadata()
        );
    }

    public static function loadFixtures(): void
    {
        $em = static::getEntityManager();

        $relationId = 0;
        for ($entityId = 1; $entityId <= 62; ++$entityId) {
            $entity = new Entity();
            $entity->setEntityId($entityId);
            $entity->setTitle('Entity '.$entityId);
            $em->persist($entity);

            for ($i = 0; $i <= $entityId % 3; ++$i) {
                ++$relationId;
                $relation = new Relation();
                $relation->setRelationId($relationId);
                $relation->setTitle('Relation '.$relationId);
                $entity->addRelation($relation);
                $em->persist($relation);
            }
        }

        $em->flush();
        $em->clear();

        $em->getConfiguration()->getSQLLogger()->reset();
    }
}
