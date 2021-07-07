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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\Command;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Tester\CommandTester;

class RectorCommandTest extends KernelTestCase
{
    private $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();

        if (!file_exists('vendor/bin/rector')) {
            $this->markTestSkipped();
        }

        $application = new Application(static::$kernel);
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);

        $command = $application->find('api:rector:upgrade');
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @requires PHP 8.0
     */
    public function testExecuteForbiddenOperationsWithApiPlatformV2()
    {
        $operations = ['annotation-to-api-resource', 'attribute-to-api-resource', 'keep-attribute'];

        foreach ($operations as $operation) {
            $this->expectException(InvalidOptionException::class);
            $this->commandTester->execute([
                'src' => 'tests/Fixtures/TestBundle/Entity/SecuredDummy.php',
                '--'.$operation => null,
                '--silent' => null,
            ]);
        }
    }

    /**
     * @requires PHP 8.0
     */
    public function testExecuteAllowedOperationsWithApiPlatformV2()
    {
        $operations = ['annotation-to-legacy-api-resource'];

        foreach ($operations as $operation) {
            $this->commandTester->setInputs(['yes']);

            $this->commandTester->execute([
                'src' => 'tests/Fixtures/TestBundle/Entity',
                '--'.$operation => null,
                '--dry-run' => null,
                '--silent' => null,
            ]);

            $this->assertStringContainsString('Migration successful.', $this->commandTester->getDisplay());
        }
    }

    /**
     * @requires PHP 8.0
     */
    public function testExecuteOperationsWithApiPlatformV3()
    {
        if (!class_exists(\ApiPlatform\Metadata\ApiResource::class)) {
            $this->markTestSkipped();
        }

        $operations = ['annotation-to-legacy-api-resource', 'annotation-to-api-resource', 'attribute-to-api-resource', 'keep-attribute'];

        foreach ($operations as $operation) {
            $this->commandTester->execute([
                'src' => 'tests/Fixtures/TestBundle/Entity',
                '--'.$operation => null,
                '--dry-run' => null,
                '--silent' => null,
            ]);

            $this->assertStringContainsString('Migration successful.', $this->commandTester->getDisplay());
        }
    }

    /**
     * @requires PHP 8.0
     */
    public function testExecuteCancelOperation()
    {
        $this->commandTester->setInputs([0, 'no']);

        $this->commandTester->execute([
            'src' => 'tests/Fixtures/TestBundle/Entity',
            '--silent' => null,
        ]);

        $this->assertStringContainsString('Migration aborted.', $this->commandTester->getDisplay());
    }

    /**
     * @requires PHP 8.0
     */
    public function testExecuteWithWrongInput()
    {
        $this->expectException(MissingInputException::class);

        $this->commandTester->setInputs([4, 'yes']);

        $this->commandTester->execute([
            'src' => 'tests/Fixtures/TestBundle/Entity',
            '--silent' => null,
        ]);
    }

    /**
     * @requires PHP 8.0
     */
    public function testExecuteWithTooMuchOptions()
    {
        if (!class_exists(\ApiPlatform\Metadata\ApiResource::class)) {
            $this->markTestSkipped();
        }

        $this->commandTester->execute([
            'src' => 'tests/Fixtures/TestBundle/Entity',
            '--annotation-to-api-resource' => null,
            '--keep-attribute' => null,
            '--dry-run' => null,
            '--silent' => null,
        ]);

        $this->assertSame('Only one operation can be given as a parameter.', $this->commandTester->getDisplay());
    }
}
