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

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Ecommit\DoctrineUtils\QueryBuilderFilter;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DoctrineORMPaginator extends AbstractDoctrinePaginator
{
    protected function buildCount(): int
    {
        $count = $this->getOption('count');
        if (\is_int($count)) {
            return $count;
        }

        return DoctrinePaginatorBuilder::countQueryBuilder(array_merge(
            $count,
            ['query_builder' => $this->getOption('query_builder')]
        ));
    }

    protected function buildIterator(): \Traversable
    {
        if (0 === $this->count()) {
            return new \ArrayIterator([]);
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = clone $this->getOption('query_builder');

        if (null === $this->getOption('by_identifier')) {
            $this->setOffsetAndLimit($queryBuilder);

            $doctrinePaginator = new Paginator($queryBuilder, $this->getOption('fetch_join_collection'));
            $doctrinePaginator->setUseOutputWalkers(!$this->getOption('simplified_request'));

            return $doctrinePaginator->getIterator();
        }

        $idsQueryBuilder = clone $queryBuilder;
        $idsQueryBuilder->select(sprintf('DISTINCT %s as pk', $this->getOption('by_identifier')));
        $this->setOffsetAndLimit($idsQueryBuilder);
        $doctrinePaginator = new Paginator($idsQueryBuilder, false);
        $doctrinePaginator->setUseOutputWalkers(true);
        $ids = array_map(function ($row) {
            return $row['pk'];
        }, $doctrinePaginator->getIterator()->getArrayCopy());

        $resultsByIdsQueryBuilder = clone $queryBuilder;
        $resultsByIdsQueryBuilder->resetDQLPart('where');
        $resultsByIdsQueryBuilder->setParameters([]);
        QueryBuilderFilter::addMultiFilter($resultsByIdsQueryBuilder, QueryBuilderFilter::SELECT_IN, $ids, $this->getOption('by_identifier'), 'paginate_pks');

        return new \ArrayIterator($resultsByIdsQueryBuilder->getQuery()->getResult());
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $this->defineDoctrineOptions($resolver);
        $resolver->setDefaults([
            'simplified_request' => null,
            'fetch_join_collection' => null,
        ]);
        $resolver->setAllowedTypes('query_builder', QueryBuilder::class);
        $resolver->setNormalizer('count', function (Options $options, $count) {
            if (\is_int($count)) {
                return $count;
            }
            $countBehavior = (isset($count['behavior'])) ? $count['behavior'] : DoctrinePaginatorBuilder::getDefaultCountBehavior($options['query_builder']);
            if ('orm' === $countBehavior && !isset($count['simplified_request'])) {
                $count['simplified_request'] = $options['simplified_request'];
            }

            return $count;
        });
        $resolver->setAllowedTypes('simplified_request', ['bool', 'null']);
        $resolver->setNormalizer('simplified_request', function (Options $options, $simplifiedRequest): ?bool {
            if (null === $options['by_identifier'] && null === $simplifiedRequest) {
                return true;
            } elseif (null !== $options['by_identifier'] && null !== $simplifiedRequest) {
                throw new InvalidOptionsException('The "simplified_request" option can only be used when "by_identifier" option is not set');
            }

            return $simplifiedRequest;
        });
        $resolver->setAllowedTypes('fetch_join_collection', ['bool', 'null']);
        $resolver->setNormalizer('fetch_join_collection', function (Options $options, $fetchJoinCollection): ?bool {
            if (null === $options['by_identifier'] && null === $fetchJoinCollection) {
                return false;
            } elseif (null !== $options['by_identifier'] && null !== $fetchJoinCollection) {
                throw new InvalidOptionsException('The "fetch_join_collection" option can only be used when "by_identifier" option is not set');
            }

            return $fetchJoinCollection;
        });
    }
}
