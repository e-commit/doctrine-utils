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
use Doctrine\DBAL\Result;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder as QueryBuilderORM;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-type CountOptions array{
 *      query_builder: QueryBuilderDBAL|QueryBuilderORM,
 *      behavior?: 'count_by_alias'|'count_by_sub_request'|'orm',
 *      alias?: ?string,
 *      distinct_alias?: ?bool,
 *      simplified_request?: ?bool
 * }
 * @phpstan-type CountResolvedOptions array{
 *      query_builder: QueryBuilderDBAL|QueryBuilderORM,
 *      behavior: 'count_by_alias'|'count_by_sub_request'|'orm',
 *      alias: ?string,
 *      distinct_alias: ?bool,
 *      simplified_request: ?bool
 * }
 */
class DoctrinePaginatorBuilder
{
    /**
     * @param CountOptions $options Availabled options :
     *                              * query_builder - Required
     *                              * behavior
     *                              * alias [ONLY WITH behavior=count_by_alias]
     *                              * distinct_alias [ONLY WITH behavior=count_by_alias]
     *                              * simplified_request - Remove unnecessary "select" statements [ONLY WITH ORM QUERY BUILDER AND WITH behavior=orm ]
     *
     *                              Availabled behaviors :
     *                              * count_by_alias: Use alias. Option "alias" is required
     *                              * count_by_sub_request: Use sub request
     *                              * orm: Use Doctrine ORM Paginator [ONLY WITH ORM QUERY BUILDER]
     *
     * @return int<0, max>
     */
    final public static function countQueryBuilder(array $options): int
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired('query_builder');
        $resolver->setDefaults([
            'behavior' => function (Options $options): string {
                /** @var QueryBuilderDBAL|QueryBuilderORM $queryBuilder */
                $queryBuilder = $options['query_builder'];

                return self::getDefaultCountBehavior($queryBuilder);
            },
            'alias' => null,
            'distinct_alias' => null,
            'simplified_request' => null,
        ]);
        $resolver->setAllowedTypes('query_builder', [QueryBuilderDBAL::class, QueryBuilderORM::class]);
        $resolver->setAllowedTypes('behavior', 'string');
        $resolver->setAllowedValues('behavior', function (string $behavior) use ($options): bool {
            if ($options['query_builder'] instanceof QueryBuilderDBAL) {
                return \in_array($behavior, ['count_by_alias', 'count_by_sub_request']);
            }

            return \in_array($behavior, ['count_by_alias', 'count_by_sub_request', 'orm']);
        });
        $resolver->setAllowedTypes('alias', ['string', 'null']);
        $resolver->setNormalizer('alias', function (Options $options, ?string $alias): ?string {
            if ('count_by_alias' === $options['behavior'] && null === $alias) {
                throw new MissingOptionsException('When "behavior" option is set to "count_by_alias", "alias" option is required');
            } elseif ('count_by_alias' !== $options['behavior'] && null !== $alias) {
                throw new InvalidOptionsException('The "alias" option can only be used when "behavior" option is set to "count_by_alias"');
            }

            return $alias;
        });
        $resolver->setAllowedTypes('distinct_alias', ['bool', 'null']);
        $resolver->setNormalizer('distinct_alias', function (Options $options, ?bool $distinctAlias): ?bool {
            if ('count_by_alias' === $options['behavior'] && null === $distinctAlias) {
                return true;
            } elseif ('count_by_alias' !== $options['behavior'] && null !== $distinctAlias) {
                throw new InvalidOptionsException('The "distinct_alias" option can only be used when "behavior" option is set to "count_by_alias"');
            }

            return $distinctAlias;
        });
        $resolver->setAllowedTypes('simplified_request', ['bool', 'null']);
        $resolver->setNormalizer('simplified_request', function (Options $options, ?bool $simplifiedRequest): ?bool {
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
        /** @var CountResolvedOptions $options */
        $options = $resolver->resolve($options);

        if ($options['query_builder'] instanceof QueryBuilderDBAL) {
            return self::countQueryBuilderDBAL($options['query_builder'], $options);
        }

        return self::countQueryBuilderORM($options['query_builder'], $options);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return DoctrineDBALPaginator|DoctrineORMPaginator
     */
    final public static function createDoctrinePaginator(QueryBuilderDBAL|QueryBuilderORM $queryBuilder, mixed $page, int $maxPerPage, array $options = []): AbstractDoctrinePaginator
    {
        $options = array_merge($options, [
            'query_builder' => $queryBuilder,
            'page' => $page,
            'max_per_page' => $maxPerPage,
        ]);

        if ($queryBuilder instanceof QueryBuilderDBAL) {
            return new DoctrineDBALPaginator($options); // @phpstan-ignore-line
        }

        return new DoctrineORMPaginator($options); // @phpstan-ignore-line
    }

    /**
     * @param CountResolvedOptions $options
     *
     * @return int<0, max>
     */
    private static function countQueryBuilderDBAL(QueryBuilderDBAL $queryBuilder, array $options): int
    {
        if ('count_by_alias' === $options['behavior']) {
            $countQueryBuilder = clone $queryBuilder;

            $distinct = ($options['distinct_alias']) ? 'DISTINCT ' : '';
            $countQueryBuilder->select(\sprintf('count(%s%s)', $distinct, $options['alias']));
            $countQueryBuilder->resetQueryPart('orderBy');
            $result = $countQueryBuilder->execute();
            if (!$result instanceof Result) {
                throw new \Exception('Expected class : '.Result::class);
            }
            $count = $result->fetchOne();
            if (false === $count) {
                throw new \Exception('Mixed result expected');
            }
            /** @var int<0, max> $count */
            $count = (int) $count; // @phpstan-ignore-line

            return $count;
        }

        // count_by_sub_request
        $queryBuilderCount = clone $queryBuilder;
        $queryBuilderClone = clone $queryBuilder;

        $queryBuilderClone->resetQueryPart('orderBy');

        $queryBuilderCount->resetQueryParts(); // Remove Query Parts
        $queryBuilderCount->select('count(*)')
            ->from('('.$queryBuilderClone->getSql().')', 'mainquery');
        $result = $queryBuilderCount->execute();

        if (!$result instanceof Result) {
            throw new \Exception('Expected class : '.Result::class);
        }
        $count = $result->fetchOne();
        if (false === $count) {
            throw new \Exception('Mixed result expected');
        }
        /** @var int<0, max> $count */
        $count = (int) $count; // @phpstan-ignore-line

        return $count;
    }

    /**
     * @param CountResolvedOptions $options
     *
     * @return int<0, max>
     */
    private static function countQueryBuilderORM(QueryBuilderORM $queryBuilder, array $options): int
    {
        if ('orm' === $options['behavior']) {
            $cloneQueryBuilder = clone $queryBuilder;

            $doctrinePaginator = new Paginator($cloneQueryBuilder->getQuery());
            $doctrinePaginator->setUseOutputWalkers(!$options['simplified_request']);
            /** @var int<0, max> $count */
            $count = $doctrinePaginator->count();

            return $count;
        } elseif ('count_by_alias' === $options['behavior']) {
            $countQueryBuilder = clone $queryBuilder;

            $distinct = ($options['distinct_alias']) ? 'DISTINCT ' : '';
            $countQueryBuilder->select(\sprintf('count(%s%s)', $distinct, $options['alias']));
            $countQueryBuilder->resetDQLPart('orderBy');
            /** @var int<0, max> $count */
            $count = (int) $countQueryBuilder->getQuery()->getSingleScalarResult();

            return $count;
        }

        // count_by_sub_request
        $cloneQueryBuilder = clone $queryBuilder;

        $cloneQueryBuilder->resetDQLPart('orderBy');
        $sql = $cloneQueryBuilder->getQuery()->getSQL();
        if (!\is_string($sql)) {
            throw new \Exception('Query builder is not compatible (multiple SQL queries)');
        }
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('cnt', 'cnt');
        $countSql = \sprintf('SELECT count(*) as cnt FROM (%s) mainquery', $sql);
        $countQuery = $queryBuilder->getEntityManager()->createNativeQuery($countSql, $rsm);
        $i = 0;
        foreach ($queryBuilder->getParameters() as $parameter) {
            ++$i;
            /** @var int|string|null $parameterType */
            $parameterType = $parameter->getType();
            $countQuery->setParameter($i, $parameter->getValue(), $parameterType);
        }

        return (int) $countQuery->getSingleScalarResult(); // @phpstan-ignore-line
    }

    public static function getDefaultCountBehavior(QueryBuilderDBAL|QueryBuilderORM $queryBuilder): string
    {
        if ($queryBuilder instanceof QueryBuilderDBAL) {
            return 'count_by_sub_request';
        }

        return 'orm';
    }
}
