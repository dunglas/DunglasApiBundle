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
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

class MakeDataProvider extends AbstractMaker
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
        return 'make:data-provider';
    }

    /**
     * {@inheritdoc}
     */
    public static function getCommandDescription(): string
    {
        return 'Creates an API Platform data provider';
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'Choose a class name for your data provider (e.g. <fg=yellow>AwesomeDataProvider</>)')
            ->addArgument('resource-class', InputArgument::OPTIONAL, 'Choose a Resource class')
            ->addOption('item-only', null, InputOption::VALUE_NONE, 'Generate only an item data provider')
            ->addOption('collection-only', null, InputOption::VALUE_NONE, 'Generate only a collection data provider')
            ->setHelp(file_get_contents(__DIR__.'/Resources/help/MakeDataProvider.txt'));

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
        if ($input->getOption('item-only') && $input->getOption('collection-only')) {
            throw new RuntimeCommandException('You should at least generate an item or a collection data provider');
        }

        if (null === $input->getArgument('resource-class')) {
            $argument = $command->getDefinition()->getArgument('resource-class');

            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($this->resourceNameCollection->create());

            $input->setArgument('resource-class', $io->askQuestion($question));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $dataProviderClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'DataProvider\\'
        );
        $resourceClass = $input->getArgument('resource-class');

        $generator->generateClass(
            $dataProviderClassNameDetails->getFullName(),
            __DIR__.'/Resources/skeleton/DataProvider.tpl.php',
            [
                'resource_class' => null !== $resourceClass ? Str::getShortClassName($resourceClass) : null,
                'resource_full_class_name' => $resourceClass,
                'generate_collection' => !$input->getOption('item-only'),
                'generate_item' => !$input->getOption('collection-only'),
            ]
        );
        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text([
            'Next: Open your new data provider class and start customizing it.',
            'Find the documentation at <fg=yellow>https://api-platform.com/docs/core/data-providers/</>',
        ]);
    }
}
