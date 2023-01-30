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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="relation")
 */
class Relation
{
    /**
     * @ORM\Id
     *
     * @ORM\Column(type="integer", name="relation_id")
     */
    protected ?int $relationId = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected ?string $title = null;

    /**
     * @ORM\ManyToOne(targetEntity="Ecommit\DoctrineUtils\Tests\App\Entity\Entity", inversedBy="relations")
     *
     * @ORM\JoinColumn(name="entity_id", referencedColumnName="entity_id", nullable=false)
     */
    protected ?Entity $entity = null;

    public function setRelationId(?int $relationId): self
    {
        $this->relationId = $relationId;

        return $this;
    }

    public function getRelationId(): ?int
    {
        return $this->relationId;
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

    public function setEntity(?Entity $entity = null): self
    {
        $this->entity = $entity;

        return $this;
    }

    public function getEntity(): ?Entity
    {
        return $this->entity;
    }
}
