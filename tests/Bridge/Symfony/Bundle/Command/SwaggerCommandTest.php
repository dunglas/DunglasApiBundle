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

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class SwaggerCommandTest extends KernelTestCase
{
    /**
     * @var ApplicationTester
     */
    private $tester;

    protected function setUp(): void
    {
        self::bootKernel();

        $application = new Application(static::$kernel);
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);

        $this->tester = new ApplicationTester($application);
    }

    /**
     * @group legacy
     * @expectedDeprecation The command "api:swagger:export" is deprecated for the spec version 3 use "api:openapi:export".
     */
    public function testExecuteWithAliasVersion3()
    {
        $this->tester->run(['command' => 'api:swagger:export', '--spec-version' => 3]);

        $this->assertJson($this->tester->getDisplay());
    }

    /**
     * @group legacy
     * @expectedDeprecation The command "api:swagger:export" is deprecated for the spec version 3 use "api:openapi:export".
     */
    public function testExecuteWithYamlVersion3()
    {
        $this->tester->run(['command' => 'api:swagger:export', '--yaml' => true, '--spec-version' => 3]);

        $result = $this->tester->getDisplay();
        $this->assertYaml($result);

        $expected = <<<YAML
  /dummy_cars:
    get:
      operationId: '_api_/dummy_cars.{_format}_get'
      tags:
        - DummyCar
YAML;

        // Windows uses \r\n as PHP_EOL but symfony exports YAML with \n
        $this->assertStringContainsString(str_replace(\PHP_EOL, "\n", $expected), $result, 'nested object should be present.');

        $expected = <<<YAML
  '/dummy_cars/{id}':
    get:
      tags: []
      operationId: '_api_/dummy_cars/{id}.{_format}_get'
YAML;

        $this->assertStringContainsString(str_replace(\PHP_EOL, "\n", $expected), $result, 'arrays should be correctly formatted.');
        $this->assertStringContainsString('openapi: 3.0.2', $result);

        $expected = <<<YAML
info:
  title: 'My Dummy API'
  version: 0.0.0
YAML;
        $this->assertStringContainsString(str_replace(\PHP_EOL, "\n", $expected), $result, 'multiline formatting must be preserved (using literal style).');

        $expected = <<<YAML
    This is a test API.
    Made with love
YAML;

        $this->assertStringContainsString(str_replace(\PHP_EOL, "\n", $expected), $result);
    }

    public function testExecuteWithBadArguments()
    {
        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage('This tool only supports versions 2, 3 of the OpenAPI specification ("foo" given).');
        $this->tester->run(['command' => 'api:swagger:export', '--spec-version' => 'foo', '--yaml' => true]);
    }

    public function testWriteToFile()
    {
        /** @var string $tmpFile */
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_write_to_file');

        $this->tester->run(['command' => 'api:swagger:export', '--output' => $tmpFile]);

        $this->assertJson((string) @file_get_contents($tmpFile));
        @unlink($tmpFile);
    }

    /**
     * @param string $data
     */
    private function assertYaml($data)
    {
        try {
            Yaml::parse($data);
        } catch (ParseException $exception) {
            $this->fail('Is not valid YAML: '.$exception->getMessage());
        }
        $this->addToAssertionCount(1);
    }
}
