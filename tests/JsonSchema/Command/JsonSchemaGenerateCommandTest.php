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

namespace ApiPlatform\Core\Tests\JsonSchema\Command;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy as DocumentDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @author Jacques Lefebvre <jacques@les-tilleuls.coop>
 */
class JsonSchemaGenerateCommandTest extends KernelTestCase
{
    /**
     * @var ApplicationTester
     */
    private $tester;

    private $entityClass;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $application = new Application(static::$kernel);
        $application->setCatchExceptions(true);
        $application->setAutoExit(false);

        $this->entityClass = 'mongodb' === $kernel->getEnvironment() ? DocumentDummy::class : Dummy::class;
        $this->tester = new ApplicationTester($application);
    }

    public function testExecuteWithoutOption()
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass]);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithItemOperationGet()
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--itemOperation' => '_api_/dummies/{id}.{_format}_get', '--type' => 'output']);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithCollectionOperationGet()
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--collectionOperation' => '_api_/dummies.{_format}_get', '--type' => 'output']);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithTooManyOptions()
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--collectionOperation' => '_api_/dummies.{_format}_get', '--itemOperation' => '_api_/dummies/{id}.{_format}_get', '--type' => 'output']);

        $this->assertStringStartsWith('[ERROR] You can only use one of "--itemOperation" and "--collectionOperation" options at the same time.', trim(preg_replace('/\s+/', ' ', $this->tester->getDisplay())));
    }

    public function testExecuteWithJsonldFormatOption()
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--collectionOperation' => '_api_/dummies.{_format}_post', '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();

        $this->assertStringContainsString('@id', $result);
        $this->assertStringContainsString('@context', $result);
        $this->assertStringContainsString('@type', $result);
    }
}
