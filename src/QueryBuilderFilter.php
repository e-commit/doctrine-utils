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

namespace Ecommit\DoctrineUtils;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as QueryBuilderDBAL;
use Doctrine\ORM\QueryBuilder as QueryBuilderORM;

class QueryBuilderFilter
{
    public const SELECT_IN = 'IN'; // WHERE IN
    public const SELECT_NOT_IN = 'NIN'; // WHERE NOT IN
    public const SELECT_ALL = 'ALL'; // No Filter (all values)
    public const SELECT_AUTO = 'AUT'; // WHERE IN. If filter values are empty, no filter (all values)
    public const SELECT_NO = 'NO'; // Must return no result

    public const MAX_PER_IN = 1000;

    /**
     * Add SQL WHERE IN or WHERE NOT IN filter.
     *
     * @param self::SELECT_* $filterSign   ALL (no filter), IN (WHERE IN), NIN (WHERE NOT IN), AUT (WHERE IN if $filterValues is not empty. No filter else), NO (no result)
     * @param array<mixed>   $filterValues Values
     * @param string         $sqlField     SQL field name
     * @param string         $paramName    SQL parameter name
     */
    final public static function addMultiFilter(QueryBuilderDBAL|QueryBuilderORM $queryBuilder, string $filterSign, array $filterValues, string $sqlField, string $paramName): QueryBuilderDBAL|QueryBuilderORM
    {
        if (self::SELECT_NO === $filterSign) {
            // Must return no result
            $queryBuilder->andWhere('0 = 1');

            return $queryBuilder;
        }
        if (self::SELECT_ALL === $filterSign) {
            return $queryBuilder;
        }
        if (self::SELECT_IN !== $filterSign && self::SELECT_NOT_IN !== $filterSign && self::SELECT_AUTO !== $filterSign) {
            throw new \Exception('Bad filter sign');
        }
        if (0 === \count($filterValues)) {
            if (self::SELECT_NOT_IN === $filterSign || self::SELECT_AUTO === $filterSign) {
                return $queryBuilder;
            }

            // Must return no result
            $queryBuilder->andWhere('0 = 1');

            return $queryBuilder;
        }

        if (\count($filterValues) > self::MAX_PER_IN) {
            return self::addGroupMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName);
        }

        return self::addSimpleMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName);
    }

    /**
     * Add SQL WHERE IN or WHERE NOT IN filter without group.
     *
     * @param self::SELECT_* $filterSign   ALL (no filter), IN (WHERE IN), NIN (WHERE NOT IN), AUT (WHERE IN if $filterValues is not empty. No filter else), NO (no result)
     * @param array<mixed>   $filterValues Values
     * @param string         $sqlField     SQL field name
     * @param string         $paramName    SQL parameter name
     */
    private static function addSimpleMultiFilter(QueryBuilderDBAL|QueryBuilderORM $queryBuilder, string $filterSign, array $filterValues, string $sqlField, string $paramName): QueryBuilderDBAL|QueryBuilderORM
    {
        $clauseSql = (self::SELECT_IN === $filterSign || self::SELECT_AUTO === $filterSign) ? 'IN' : 'NOT IN';

        $queryBuilder->andWhere(\sprintf('%s %s (:%s)', $sqlField, $clauseSql, $paramName));
        $queryBuilder->setParameter($paramName, $filterValues, Connection::PARAM_STR_ARRAY);

        return $queryBuilder;
    }

    /**
     * Add SQL WHERE IN or WHERE NOT IN filter with group.
     *
     * @param self::SELECT_* $filterSign   ALL (no filter), IN (WHERE IN), NIN (WHERE NOT IN), AUT (WHERE IN if $filterValues is not empty. No filter else), NO (no result)
     * @param array<mixed>   $filterValues Values
     * @param string         $sqlField     SQL field name
     * @param string         $paramName    SQL parameter name
     */
    private static function addGroupMultiFilter(QueryBuilderDBAL|QueryBuilderORM $queryBuilder, string $filterSign, array $filterValues, string $sqlField, string $paramName): QueryBuilderDBAL|QueryBuilderORM
    {
        $clauseSql = (self::SELECT_IN === $filterSign || self::SELECT_AUTO === $filterSign) ? 'IN' : 'NOT IN';
        $separatorClauseSql = (self::SELECT_IN === $filterSign || self::SELECT_AUTO === $filterSign) ? 'OR' : 'AND';

        $groupNumber = 0;
        $groups = [];
        foreach (array_chunk($filterValues, self::MAX_PER_IN) as $filterValuesGroup) {
            ++$groupNumber;
            $groups[] = \sprintf('%s %s (:%s%s)', $sqlField, $clauseSql, $paramName, $groupNumber);
            $queryBuilder->setParameter($paramName.$groupNumber, $filterValuesGroup, Connection::PARAM_STR_ARRAY);
        }

        $queryBuilder->andWhere(implode(' '.$separatorClauseSql.' ', $groups));

        return $queryBuilder;
    }

    /**
     * Add SQL WHERE IN or WHERE NOT IN filter. And result MUST BE in the whitelist (if $restrictSign=IN) or MUST NOT BE in the blacklist (if $restrictSign=NIN).
     *
     * @param self::SELECT_* $filterSign     ALL (no filter), IN (WHERE IN), NIN (WHERE NOT IN), AUT (WHERE IN if $filterValues is not empty. No filter else), NO (no result)
     * @param array<mixed>   $filterValues   Values
     * @param string         $sqlField       SQL field name
     * @param string         $paramName      SQL parameter name
     * @param self::SELECT_* $restrictSign   IN (WHERE IN), NIN (WHERE NOT IN), AUT (WHERE IN if $restrictValues is not empty. No filter else), NO (no result)
     * @param array<mixed>   $restrictValues Whitelist (if $restrictSign=IN or AUT) or blacklist (if $restrictSign=NIN)
     */
    final public static function addMultiFilterWithRestrictValues(QueryBuilderDBAL|QueryBuilderORM $queryBuilder, string $filterSign, array $filterValues, string $sqlField, string $paramName, string $restrictSign, array $restrictValues): QueryBuilderDBAL|QueryBuilderORM
    {
        if (self::SELECT_NO === $filterSign || self::SELECT_NO === $restrictSign) {
            // Must return no result
            $queryBuilder->andWhere('0 = 1');

            return $queryBuilder;
        }

        if (\in_array($restrictSign, [self::SELECT_IN, self::SELECT_AUTO]) && \in_array($filterSign, [self::SELECT_IN, self::SELECT_AUTO]) && \count($filterValues) > 0 && \count($restrictValues) > 0) {
            // We can simplify the query

            // Data cleaning
            $cleanValues = [];
            foreach ($filterValues as $value) {
                if (\in_array($value, $restrictValues)) {
                    $cleanValues[] = $value;
                }
            }

            $queryBuilder = self::addMultiFilter($queryBuilder, self::SELECT_IN, $cleanValues, $sqlField, $paramName);
        } elseif (self::SELECT_NOT_IN === $restrictSign && self::SELECT_NOT_IN === $filterSign && \count($filterValues) > 0 && \count($restrictValues) > 0) {
            // We can simplify the query

            // Data fusion
            $cleanValues = $restrictValues;
            foreach ($filterValues as $value) {
                if (!\in_array($value, $restrictValues)) {
                    $cleanValues[] = $value;
                }
            }

            $queryBuilder = self::addMultiFilter($queryBuilder, self::SELECT_NOT_IN, $cleanValues, $sqlField, $paramName);
        } else {
            // Two filters
            self::addMultiFilter($queryBuilder, $filterSign, $filterValues, $sqlField, $paramName);
            self::addMultiFilter($queryBuilder, $restrictSign, $restrictValues, $sqlField, $paramName.'Restrict');
        }

        return $queryBuilder;
    }

    /**
     * Add SQL "equal" or "not equal" filter.
     *
     * @param bool   $equal       Equal or not
     * @param mixed  $filterValue Value
     * @param string $sqlField    SQL field name
     * @param string $paramName   SQL parameter name
     */
    final public static function addEqualFilter(QueryBuilderDBAL|QueryBuilderORM $queryBuilder, bool $equal, mixed $filterValue, string $sqlField, string $paramName): QueryBuilderDBAL|QueryBuilderORM
    {
        if (null === $filterValue || '' === $filterValue) {
            return $queryBuilder;
        }

        if ($equal) {
            $queryBuilder->andWhere($sqlField.' = :'.$paramName);
        } else {
            $queryBuilder->andWhere($sqlField.' != :'.$paramName);
        }
        $queryBuilder->setParameter($paramName, $filterValue);

        return $queryBuilder;
    }

    /**
     * Add SQL comparator filter.
     *
     * @param '<'|'>'|'<='|'>=' $sign        Comparator sign (< > <= >=)
     * @param mixed             $filterValue Value
     * @param string            $sqlField    SQL field name
     * @param string            $paramName   SQL parameter name
     */
    final public static function addComparatorFilter(QueryBuilderDBAL|QueryBuilderORM $queryBuilder, string $sign, $filterValue, string $sqlField, string $paramName): QueryBuilderDBAL|QueryBuilderORM
    {
        if (null === $filterValue || '' === $filterValue) {
            return $queryBuilder;
        }

        $queryBuilder->andWhere(\sprintf('%s %s :%s', $sqlField, $sign, $paramName));
        $queryBuilder->setParameter($paramName, $filterValue);

        return $queryBuilder;
    }

    /**
     * Add SQL "LIKE" or "NOT LIKE" filter.
     *
     * @param bool   $contain     Contain or not
     * @param string $filterValue Value
     * @param string $sqlField    SQL field name
     * @param string $paramName   SQL parameter name
     */
    final public static function addContainFilter(QueryBuilderDBAL|QueryBuilderORM $queryBuilder, bool $contain, ?string $filterValue, string $sqlField, string $paramName): QueryBuilderDBAL|QueryBuilderORM
    {
        if (null === $filterValue || '' === $filterValue) {
            return $queryBuilder;
        }

        $filterValue = addcslashes($filterValue, '%_');
        if ($contain) {
            $queryBuilder->andWhere($queryBuilder->expr()->like($sqlField, ':'.$paramName));
        } else {
            $queryBuilder->andWhere($queryBuilder->expr()->notLike($sqlField, ':'.$paramName));
        }
        $queryBuilder->setParameter($paramName, '%'.$filterValue.'%');

        return $queryBuilder;
    }
}
