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

namespace Ecommit\DoctrineUtils\Paginator;

use Doctrine\DBAL\Query\QueryBuilder as QueryBuilderDBAL;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder as QueryBuilderORM;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DoctrinePaginatorBuilder
{
    /**
     * @param array $options Availabled options :
     *                       * query_builder - Required
     *                       * behavior
     *                       * alias [ONLY WITH behavior=count_by_alias]
     *                       * distinct_alias [ONLY WITH behavior=count_by_alias]
     *                       * simplified_request - Remove unnecessary "select" statements [ONLY WITH ORM QUERY BUILDER AND WITH behavior=orm ]
     *
     *                       Availabled behaviors :
     *                       * count_by_alias: Use alias. Option "alias" is required
     *                       * count_by_sub_request: Use sub request
     *                       * orm: Use Doctrine ORM Paginator [ONLY WITH ORM QUERY BUILDER]
     */
    final public static function countQueryBuilder(array $options = []): int
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired('query_builder');
        $resolver->setDefaults([
            'behavior' => function (Options $options): string {
                return self::getDefaultCountBehavior($options['query_builder']);
            },
            'alias' => null,
            'distinct_alias' => null,
            'simplified_request' => null,
        ]);
        $resolver->setAllowedTypes('query_builder', [QueryBuilderDBAL::class, QueryBuilderORM::class]);
        $resolver->setAllowedTypes('behavior', 'string');
        $resolver->setAllowedValues('behavior', function ($behavior) use ($options): bool {
            if ($options['query_builder'] instanceof QueryBuilderDBAL) {
                return \in_array($behavior, ['count_by_alias', 'count_by_sub_request']);
            }

            return \in_array($behavior, ['count_by_alias', 'count_by_sub_request', 'orm']);
        });
        $resolver->setAllowedTypes('alias', ['string', 'null']);
        $resolver->setNormalizer('alias', function (Options $options, $alias): ?string {
            if ('count_by_alias' === $options['behavior'] && null === $alias) {
                throw new MissingOptionsException('When "behavior" option is set to "count_by_alias", "alias" option is required');
            } elseif ('count_by_alias' !== $options['behavior'] && null !== $alias) {
                throw new InvalidOptionsException('The "alias" option can only be used when "behavior" option is set to "count_by_alias"');
            }

            return $alias;
        });
        $resolver->setAllowedTypes('distinct_alias', ['bool', 'null']);
        $resolver->setNormalizer('distinct_alias', function (Options $options, $distinctAlias): ?bool {
            if ('count_by_alias' === $options['behavior'] && null === $distinctAlias) {
                return true;
            } elseif ('count_by_alias' !== $options['behavior'] && null !== $distinctAlias) {
                throw new InvalidOptionsException('The "distinct_alias" option can only be used when "behavior" option is set to "count_by_alias"');
            }

            return $distinctAlias;
        });
        $resolver->setAllowedTypes('simplified_request', ['bool', 'null']);
        $resolver->setNormalizer('simplified_request', function (Options $options, $simplifiedRequest): ?bool {
            if (null !== $simplifiedRequest && !($options['query_builder'] instanceof QueryBuilderORM)) {
                throw new InvalidOptionsException('The "simplified_request" option can only be used with ORM QueryBuilder');
            }
            if ('orm' === $options['behavior'] && null === $simplifiedRequest) {
                return true;
            } elseif ('orm' !== $options['behavior'] && null !== $simplifiedRequest) {
                throw new InvalidOptionsException('The "simplified_request" option can only be used when "behavior" option is set to "orm"');
            }

            return $simplifiedRequest;
        });
        $options = $resolver->resolve($options);

        if ($options['query_builder'] instanceof QueryBuilderDBAL) {
            return self::countQueryBuilderDBAL($options['query_builder'], $options);
        }

        return self::countQueryBuilderORM($options['query_builder'], $options);
    }

    final public static function createDoctrinePaginator($queryBuilder, $page, int $maxPerPage, array $options = []): AbstractDoctrinePaginator
    {
        $options = array_merge($options, [
            'query_builder' => $queryBuilder,
            'page' => $page,
            'max_per_page' => $maxPerPage,
        ]);

        if ($queryBuilder instanceof QueryBuilderDBAL) {
            return new DoctrineDBALPaginator($options);
        } elseif ($queryBuilder instanceof QueryBuilderORM) {
            return new DoctrineORMPaginator($options);
        }

        throw new \Exception('Bad class');
    }

    private static function countQueryBuilderDBAL(QueryBuilderDBAL $queryBuilder, array $options): int
    {
        if ('count_by_alias' === $options['behavior']) {
            $countQueryBuilder = clone $queryBuilder;

            $distinct = ($options['distinct_alias']) ? 'DISTINCT ' : '';
            $countQueryBuilder->select(sprintf('count(%s%s)', $distinct, $options['alias']));
            $countQueryBuilder->resetQueryPart('orderBy');

            return (int) $countQueryBuilder->execute()->fetchOne();
        }

        // count_by_sub_request
        $queryBuilderCount = clone $queryBuilder;
        $queryBuilderClone = clone $queryBuilder;

        $queryBuilderClone->resetQueryPart('orderBy');

        $queryBuilderCount->resetQueryParts(); // Remove Query Parts
        $queryBuilderCount->select('count(*)')
            ->from('('.$queryBuilderClone->getSql().')', 'mainquery');

        return (int) $queryBuilderCount->execute()->fetchOne();
    }

    private static function countQueryBuilderORM(QueryBuilderORM $queryBuilder, array $options): int
    {
        if ('orm' === $options['behavior']) {
            $cloneQueryBuilder = clone $queryBuilder;

            $doctrinePaginator = new Paginator($cloneQueryBuilder->getQuery());
            $doctrinePaginator->setUseOutputWalkers(!$options['simplified_request']);

            return (int) $doctrinePaginator->count();
        } elseif ('count_by_alias' === $options['behavior']) {
            $countQueryBuilder = clone $queryBuilder;

            $distinct = ($options['distinct_alias']) ? 'DISTINCT ' : '';
            $countQueryBuilder->select(sprintf('count(%s%s)', $distinct, $options['alias']));
            $countQueryBuilder->resetDQLPart('orderBy');

            return (int) $countQueryBuilder->getQuery()->getSingleScalarResult();
        }

        // count_by_sub_request
        $cloneQueryBuilder = clone $queryBuilder;

        $cloneQueryBuilder->resetDQLPart('orderBy');
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('cnt', 'cnt');
        $countSql = sprintf('SELECT count(*) as cnt FROM (%s) mainquery', $cloneQueryBuilder->getQuery()->getSQL());
        $countQuery = $queryBuilder->getEntityManager()->createNativeQuery($countSql, $rsm);
        $i = 0;
        foreach ($queryBuilder->getParameters() as $parameter) {
            ++$i;
            $countQuery->setParameter($i, $parameter->getValue(), $parameter->getType());
        }

        return (int) $countQuery->getSingleScalarResult();
    }

    public static function getDefaultCountBehavior($queryBuilder): ?string
    {
        if ($queryBuilder instanceof QueryBuilderDBAL) {
            return 'count_by_sub_request';
        } elseif ($queryBuilder instanceof QueryBuilderORM) {
            return 'orm';
        }

        return null;
    }
}
