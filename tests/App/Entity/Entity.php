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

namespace Ecommit\DoctrineUtils\Tests\App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="entity")
 */
class Entity
{
    /**
     * @ORM\Id
     *
     * @ORM\Column(type="integer", name="entity_id")
     */
    protected ?int $entityId = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected ?string $title = null;

    /**
     * @var Collection<array-key, Relation>
     *
     * @ORM\OneToMany(targetEntity="Ecommit\DoctrineUtils\Tests\App\Entity\Relation", mappedBy="entity")
     */
    protected Collection $relations;

    public function __construct()
    {
        $this->relations = new ArrayCollection();
    }

    public function setEntityId(?int $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function addRelation(Relation $relation): self
    {
        $relation->setEntity($this);
        $this->relations[] = $relation;

        return $this;
    }

    public function removeRelation(Relation $relation): self
    {
        $this->relations->removeElement($relation);
        $relation->setEntity(null);

        return $this;
    }

    /**
     * @return Collection<array-key, Relation>
     */
    public function getRelations(): Collection
    {
        return $this->relations;
    }
}
