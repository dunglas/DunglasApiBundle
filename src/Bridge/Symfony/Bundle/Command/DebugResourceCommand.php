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

namespace ApiPlatform\Bridge\Symfony\Bundle\Command;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugResourceCommand extends Command
{
    protected static $defaultName = 'debug:api';

    private $resourceCollectionMetadataFactory;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceCollectionMetadataFactory)
    {
        parent::__construct();
        $this->resourceCollectionMetadataFactory = $resourceCollectionMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Debug API Platform resources')
            ->addArgument('class');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resourceClass = $input->getArgument('class');

        $resourceCollection = $this->resourceCollectionMetadataFactory->create($resourceClass);

        if (0 === \count($resourceCollection)) {
            $output->writeln(sprintf('<error>No resources found for class %s</error>', $resourceClass));

            return 1;
        }

        $shortName = (false !== $pos = strrpos($resourceClass, '\\')) ? substr($resourceClass, $pos + 1) : $resourceClass;
        $output->writeln(sprintf('Class %s declares %d resource%s.', $shortName, \count($resourceCollection), \count($resourceCollection) > 1 ? 's' : '').\PHP_EOL);

        foreach ($resourceCollection as $resource) {
            foreach ($resource->getOperations() as $operationName => $operation) {
                $output->writeln(sprintf('%s %s', $operation->getMethod(), $operation->getUriTemplate()));
                $output->writeln('Operation name: '.$operationName);
                $output->writeln('Normalization groups: '.implode(', ', $operation->getNormalizationContext()['groups'] ?? []));
                $output->writeln('Denormalization groups: '.implode(', ', $operation->getDenormalizationContext()['groups'] ?? []));

                foreach ($operation->getIdentifiers() ?? [] as $parameter => [$class, $property]) {
                    $output->writeln(sprintf('%s: %s::%s ', $parameter, $class === $resourceClass ? $shortName : $class, $property));
                }
            }
        }

        return 0;
    }
}
