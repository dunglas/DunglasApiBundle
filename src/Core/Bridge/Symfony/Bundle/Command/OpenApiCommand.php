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

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Dumps Open API documentation.
 */
final class OpenApiCommand extends Command
{
    protected static $defaultName = 'api:openapi:export';

    private $openApiFactory;
    private $normalizer;

    public function __construct(OpenApiFactoryInterface $openApiFactory, NormalizerInterface $normalizer)
    {
        parent::__construct();
        $this->openApiFactory = $openApiFactory;
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Dump the Open API documentation')
            ->addOption('yaml', 'y', InputOption::VALUE_NONE, 'Dump the documentation in YAML')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Write output to file')
            ->addOption('spec-version', null, InputOption::VALUE_OPTIONAL, 'Open API version to use (2 or 3) (2 is deprecated)', '3')
            ->addOption('api-gateway', null, InputOption::VALUE_NONE, 'Enable the Amazon API Gateway compatibility mode');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Backwards compatibility
        if (2 === $specVersion = (int) $input->getOption('spec-version')) {
            $command = $this->getApplication()->find('api:swagger:export');

            return $command->run(new ArrayInput([
                'command' => 'api:swagger:export',
                '--spec-version' => $specVersion,
                '--yaml' => $input->getOption('yaml'),
                '--output' => $input->getOption('output'),
                '--api-gateway' => $input->getOption('api-gateway'),
            ]), $output);
        }

        $filesystem = new Filesystem();
        $io = new SymfonyStyle($input, $output);
        $data = $this->normalizer->normalize($this->openApiFactory->__invoke(), 'json');
        $content = $input->getOption('yaml')
            ? Yaml::dump($data, 10, 2, Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)
            : (json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES) ?: '');

        $filename = $input->getOption('output');
        if ($filename && \is_string($filename)) {
            $filesystem->dumpFile($filename, $content);
            $io->success(sprintf('Data written to %s.', $filename));

            return 0;
        }

        $output->writeln($content);

        return 0;
    }
}
