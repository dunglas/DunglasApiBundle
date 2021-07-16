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

namespace ApiPlatform\Core\Test;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class DoctrineOrmFilterTestCase extends KernelTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $resourceClass = Dummy::class;

    /**
     * @var string
     */
    protected $alias = 'o';

    /**
     * @var string
     */
    protected $filterClass;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine');
        $this->repository = $this->managerRegistry->getManagerForClass(Dummy::class)->getRepository(Dummy::class);
    }

    /**
     * @dataProvider provideApplyTestData
     */
    public function testApply(?array $properties, array $filterParameters, string $expectedDql, array $expectedParameters = null, callable $factory = null, string $resourceClass = null)
    {
        $this->doTestApply(false, $properties, $filterParameters, $expectedDql, $expectedParameters, $factory, $resourceClass);
    }

    /**
     * @group legacy
     * @dataProvider provideApplyTestData
     */
    public function testApplyRequest(?array $properties, array $filterParameters, string $expectedDql, array $expectedParameters = null, callable $factory = null, string $resourceClass = null)
    {
        $this->doTestApply(true, $properties, $filterParameters, $expectedDql, $expectedParameters, $factory, $resourceClass);
    }

    protected function doTestApply(bool $request, ?array $properties, array $filterParameters, string $expectedDql, array $expectedParameters = null, callable $filterFactory = null, string $resourceClass = null)
    {
        if (null === $filterFactory) {
            $filterFactory = function (ManagerRegistry $managerRegistry, array $properties = null, RequestStack $requestStack = null): FilterInterface {
                $filterClass = $this->filterClass;

                return new $filterClass($managerRegistry, $requestStack, null, $properties);
            };
        }

        $requestStack = null;
        if ($request) {
            $requestStack = new RequestStack();
            $requestStack->push(Request::create('/api/dummies', 'GET', $filterParameters));
        }

        $repository = $this->repository;
        if ($resourceClass) {
            /** @var EntityRepository $repository */
            $repository = $this->managerRegistry->getManagerForClass($resourceClass)->getRepository($resourceClass);
        }
        $resourceClass = $resourceClass ?: $this->resourceClass;
        $queryBuilder = $repository->createQueryBuilder($this->alias);
        $filterCallable = $filterFactory($this->managerRegistry, $properties, $requestStack);
        $filterCallable->apply($queryBuilder, new QueryNameGenerator(), $resourceClass, null, $request ? [] : ['filters' => $filterParameters]);

        $this->assertEquals($expectedDql, $queryBuilder->getQuery()->getDQL());

        if (null === $expectedParameters) {
            return;
        }

        foreach ($expectedParameters as $parameterName => $expectedParameterValue) {
            $queryParameter = $queryBuilder->getQuery()->getParameter($parameterName);

            $this->assertNotNull($queryParameter, sprintf('Expected query parameter "%s" to be set', $parameterName));
            $this->assertEquals($expectedParameterValue, $queryParameter->getValue(), sprintf('Expected query parameter "%s" to be "%s"', $parameterName, var_export($expectedParameterValue, true)));
        }
    }

    protected function buildFilter(?array $properties = null)
    {
        return new $this->filterClass($this->managerRegistry, null, null, $properties);
    }

    abstract public function provideApplyTestData(): array;
}
