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

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\FilterInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
abstract class DoctrineMongoDbOdmFilterTestCase extends KernelTestCase
{
    /**
     * @var DocumentManager
     */
    protected $manager;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var DocumentRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $resourceClass = Dummy::class;

    /**
     * @var string
     */
    protected $filterClass;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->manager = DoctrineMongoDbOdmTestCase::createTestDocumentManager();
        $this->managerRegistry = self::$kernel->getContainer()->get('doctrine_mongodb');
        $this->repository = $this->manager->getRepository(Dummy::class);
    }

    /**
     * @dataProvider provideApplyTestData
     */
    public function testApply(?array $properties, array $filterParameters, array $expectedPipeline, callable $factory = null, string $resourceClass = null)
    {
        $this->doTestApply($properties, $filterParameters, $expectedPipeline, $factory, $resourceClass);
    }

    protected function doTestApply(?array $properties, array $filterParameters, array $expectedPipeline, callable $filterFactory = null, string $resourceClass = null)
    {
        if (null === $filterFactory) {
            $filterFactory = function (ManagerRegistry $managerRegistry, array $properties = null): FilterInterface {
                $filterClass = $this->filterClass;

                return new $filterClass($managerRegistry, null, $properties);
            };
        }

        $repository = $this->repository;
        if ($resourceClass) {
            $repository = $this->manager->getRepository($resourceClass);
        }
        $resourceClass = $resourceClass ?: $this->resourceClass;
        $aggregationBuilder = $repository->createAggregationBuilder();
        $filterCallable = $filterFactory($this->managerRegistry, $properties);
        $context = ['filters' => $filterParameters];
        $filterCallable->apply($aggregationBuilder, $resourceClass, null, $context);
        $pipeline = [];
        try {
            $pipeline = $aggregationBuilder->getPipeline();
        } catch (\OutOfRangeException $e) {
        }

        $this->assertEquals($expectedPipeline, $pipeline);
    }

    protected function buildFilter(?array $properties = null)
    {
        return new $this->filterClass($this->managerRegistry, null, $properties);
    }

    abstract public function provideApplyTestData(): array;
}
