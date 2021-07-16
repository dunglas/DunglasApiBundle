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

namespace ApiPlatform\Core\Bridge\Symfony\Maker;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

final class MakeDataPersister extends AbstractMaker
{
    private $resourceNameCollection;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollection)
    {
        $this->resourceNameCollection = $resourceNameCollection;
    }

    /**
     * {@inheritdoc}
     */
    public static function getCommandName(): string
    {
        return 'make:data-persister';
    }

    /**
     * {@inheritdoc}
     */
    public static function getCommandDescription(): string
    {
        return 'Creates an API Platform data persister';
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a class name for your data persister (e.g. <fg=yellow>AwesomeDataPersister</>)')
            ->addArgument('resource-class', InputArgument::OPTIONAL, 'Choose a Resource class')
            ->setHelp(file_get_contents(__DIR__.'/Resources/help/MakeDataPersister.txt'));

        $inputConfig->setArgumentAsNonInteractive('resource-class');
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('resource-class')) {
            $argument = $command->getDefinition()->getArgument('resource-class');

            $resourceClasses = $this->resourceNameCollection->create();

            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($resourceClasses);

            $value = $io->askQuestion($question);

            $input->setArgument('resource-class', $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $dataPersisterClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'DataPersister\\'
        );
        $resourceClass = $input->getArgument('resource-class');

        $generator->generateClass(
            $dataPersisterClassNameDetails->getFullName(),
            __DIR__.'/Resources/skeleton/DataPersister.tpl.php',
            [
                'resource_class' => null !== $resourceClass ? Str::getShortClassName($resourceClass) : null,
                'resource_full_class_name' => $resourceClass,
            ]
        );
        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text([
            'Next: Open your new data persister class and start customizing it.',
            'Find the documentation at <fg=yellow>https://api-platform.com/docs/core/data-persisters/</>',
        ]);
    }
}
