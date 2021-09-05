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

require_once __DIR__.'/../vendor/autoload.php';

use Ecommit\DoctrineUtils\Tests\App\Doctrine;

function bootstrap(): void
{
    Doctrine::createSchema();
    Doctrine::loadFixtures();
}

bootstrap();
