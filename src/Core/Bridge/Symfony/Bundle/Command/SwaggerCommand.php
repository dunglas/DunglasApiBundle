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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Command;

use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Swagger\Serializer\ApiGatewayNormalizer;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Console command to dump Swagger API documentations.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class SwaggerCommand extends Command
{
    protected static $defaultName = 'api:swagger:export';

    private $normalizer;
    private $resourceNameCollectionFactory;
    private $apiTitle;
    private $apiDescription;
    private $apiVersion;
    private $apiFormats;
    private $swaggerVersions;

    /**
     * @param int[] $swaggerVersions
     */
    public function __construct(NormalizerInterface $normalizer, ResourceNameCollectionFactoryInterface $resourceNameCollection, string $apiTitle, string $apiDescription, string $apiVersion, array $apiFormats = null, array $swaggerVersions = [2, 3])
    {
        $this->normalizer = $normalizer;
        $this->resourceNameCollectionFactory = $resourceNameCollection;
        $this->apiTitle = $apiTitle;
        $this->apiDescription = $apiDescription;
        $this->apiVersion = $apiVersion;
        $this->apiFormats = $apiFormats;
        $this->swaggerVersions = $swaggerVersions;

        if (null !== $apiFormats) {
            @trigger_error(sprintf('Passing a 6th parameter to the constructor of "%s" is deprecated since API Platform 2.5', __CLASS__), \E_USER_DEPRECATED);
        }

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Dump the Swagger v2 documentation')
            ->addOption('yaml', 'y', InputOption::VALUE_NONE, 'Dump the documentation in YAML')
            ->addOption('spec-version', null, InputOption::VALUE_OPTIONAL, sprintf('OpenAPI version to use (%s)', implode(' or ', $this->swaggerVersions)), (string) ($this->swaggerVersions[0] ?? 2))
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Write output to file')
            ->addOption('api-gateway', null, InputOption::VALUE_NONE, 'API Gateway compatibility');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $version */
        $version = $input->getOption('spec-version');

        if (!\in_array((int) $version, $this->swaggerVersions, true)) {
            throw new InvalidOptionException(sprintf('This tool only supports versions %s of the OpenAPI specification ("%s" given).', implode(', ', $this->swaggerVersions), $version));
        }

        if (3 === (int) $version) {
            @trigger_error('The command "api:swagger:export" is deprecated for the spec version 3 use "api:openapi:export".', \E_USER_DEPRECATED);
        }

        $documentation = new Documentation($this->resourceNameCollectionFactory->create(), $this->apiTitle, $this->apiDescription, $this->apiVersion, $this->apiFormats);
        $data = $this->normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, ['spec_version' => (int) $version, ApiGatewayNormalizer::API_GATEWAY => $input->getOption('api-gateway')]);
        $content = $input->getOption('yaml')
            ? Yaml::dump($data, 10, 2, Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)
            : (json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES) ?: '');

        if (!empty($filename = $input->getOption('output')) && \is_string($filename)) {
            file_put_contents($filename, $content);
            $io->success(sprintf('Data written to %s (specification version %s).', $filename, $version));
        } else {
            $output->writeln($content);
        }

        return 0;
    }
}
