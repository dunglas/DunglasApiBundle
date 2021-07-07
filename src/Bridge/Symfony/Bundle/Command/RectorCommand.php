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

use ApiPlatform\Core\Bridge\Rector\Set\ApiPlatformSetList;
use ApiPlatform\Metadata\Resource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class RectorCommand extends Command
{
    private const OPERATIONS_V2 = [
        'annotation-to-legacy-api-resource' => '@ApiResource to Legacy #[ApiResource]',
    ];
    private const OPERATIONS = self::OPERATIONS_V2 + [
        'annotation-to-api-resource' => '@ApiResource to new #[ApiResource]',
        'attribute-to-api-resource' => '@ApiResource to legacy #[ApiResource] and new #[ApiResource]',
        'keep-attribute' => 'Legacy #[ApiResource] to new #[ApiResource]',
    ];

    protected static $defaultName = 'api:rector:upgrade';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Change legacy ApiResource annotation/attribute to new ApiResource attribute')
            ->addOption('dry-run', '-d', InputOption::VALUE_NONE, 'Rector will show you diff of files that it would change. To make the changes, drop --dry-run')
            ->addOption('silent', '-s', InputOption::VALUE_NONE, 'Run Rector silently')
            ->addArgument('src', InputArgument::REQUIRED, 'Path to folder/file to convert, forwarded to Rector');

        foreach (class_exists(\ApiPlatform\Metadata\ApiResource::class) ? self::OPERATIONS : self::OPERATIONS_V2 as $operationKey => $operationDescription) {
            $this->addOption($operationKey, null, InputOption::VALUE_NONE, $operationDescription);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!file_exists('vendor/bin/rector')) {
            $output->write('Rector is not installed. Please execute composer require --dev rector/rector-src');

            return Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $operations = class_exists(\ApiPlatform\Metadata\ApiResource::class) ? self::OPERATIONS : self::OPERATIONS_V2;

        $choices = array_values($operations);

        $choice = null;
        $operationCount = 0;

        foreach ($operations as $operationKey => $operationDescription) {
            if ($input->getOption($operationKey)) {
                $choice = $operationKey;
                ++$operationCount;
            }
        }

        if ($operationCount > 1) {
            $output->write('Only one operation can be given as a parameter.');

            return Command::FAILURE;
        }

        if (!$choice) {
            $choice = $io->choice('Choose operation to perform', $choices);
        }

        $operationKey = $this->getOperationKeyByChoice($operations, $choice);

        $command = 'vendor/bin/rector process '.$input->getArgument('src');

        if ($input->getOption('dry-run')) {
            $command .= ' --dry-run';
        } else {
            if (!$io->confirm('Your files will be overriden. Do you want to continue ?')) {
                $output->write('Migration aborted.');

                return Command::FAILURE;
            }
        }

        if ($output->isDebug()) {
            $command .= ' --debug';
        }

        $operationKeys = array_keys($operations);

        switch ($operationKey) {
            case $operationKeys[0]:
                $command .= ' --config='.ApiPlatformSetList::ANNOTATION_TO_LEGACY_API_RESOURCE_ATTRIBUTE;
                break;
            case $operationKeys[1]:
                $command .= ' --config='.ApiPlatformSetList::ANNOTATION_TO_API_RESOURCE_ATTRIBUTE;
                break;
            case $operationKeys[2]:
                $command .= ' --config='.ApiPlatformSetList::ANNOTATION_TO_LEGACY_API_RESOURCE_AND_API_RESOURCE_ATTRIBUTE;
                break;
            case $operationKeys[3]:
                $command .= ' --config='.ApiPlatformSetList::ATTRIBUTE_TO_API_RESOURCE_ATTRIBUTE;
                break;
        }

        $io->title('Run '.$command);

        if ($input->getOption('silent')) {
            exec($command);
        } else {
            passthru($command);
        }

        $output->write('Migration successful.');

        return Command::SUCCESS;
    }

    private function getOperationKeyByChoice($operations, $choice): string
    {
        if (\in_array($choice, array_keys($operations), true)) {
            return $choice;
        }

        return array_search($choice, $operations, true);
    }
}
