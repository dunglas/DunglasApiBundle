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

use ApiPlatform\Core\OpenApi\OpenApi;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class OpenApiCommandTest extends KernelTestCase
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
     * TODO: change this once we support #[Resource].
     *
     * @group legacy
     */
    public function testExecute()
    {
        $this->tester->run(['command' => 'api:openapi:export']);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithYaml()
    {
        $this->tester->run(['command' => 'api:openapi:export', '--yaml' => true]);

        $result = $this->tester->getDisplay();
        $this->assertYaml($result);

        $expected = <<<YAML
  '/dummy_cars.{_format}':
    get:
      operationId: '_api_/dummy_cars.{_format}_get'
      tags:
        - DummyCar
YAML;

        $this->assertStringContainsString(str_replace(\PHP_EOL, "\n", $expected), $result, 'nested object should be present.');

        $expected = <<<YAML
  '/dummy_cars/{id}.{_format}':
    get:
      operationId: '_api_/dummy_cars/{id}.{_format}_get'
      tags: []
YAML;

        $this->assertStringContainsString(str_replace(\PHP_EOL, "\n", $expected), $result, 'arrays should be correctly formatted.');
        $this->assertStringContainsString('openapi: '.OpenApi::VERSION, $result);

        $expected = <<<YAML
info:
  title: 'My Dummy API'
YAML;
        $this->assertStringContainsString(str_replace(\PHP_EOL, "\n", $expected), $result, 'multiline formatting must be preserved (using literal style).');

        $expected = <<<YAML
    This is a test API.
    Made with love
  version: 0.0.0
YAML;

        $this->assertStringContainsString(str_replace(\PHP_EOL, "\n", $expected), $result);
    }

    public function testWriteToFile()
    {
        /** @var string $tmpFile */
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_write_to_file');

        $this->tester->run(['command' => 'api:openapi:export', '--output' => $tmpFile]);

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
