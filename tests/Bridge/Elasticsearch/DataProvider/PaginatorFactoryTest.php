<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\DataProvider;

use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Paginator;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\PaginatorFactory;
use ApiPlatform\Core\DataProvider\PaginatorFactoryInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class PaginatorFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var PaginatorFactory
     */
    private $paginatorFactory;

    public function testConstruct()
    {
        $this->assertInstanceOf(PaginatorFactoryInterface::class, $this->paginatorFactory);
    }

    public function testCreatePaginator()
    {
        $paginator = $this->paginatorFactory->createPaginator([], 10, 0, ['resource_class' => Foo::class]);

        $this->assertInstanceOf(Paginator::class, $paginator);
    }

    public function testCreatePaginatorFailsWhenResourceClassAttributeIsMissing()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The given context array is missing the "resource_class" key.');

        $this->paginatorFactory->createPaginator([], 10, 0, ['resource_class' => null]);
    }

    protected function setUp(): void
    {
        $this->paginatorFactory = new PaginatorFactory($this->prophesize(DenormalizerInterface::class)->reveal());
    }
}
