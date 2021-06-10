<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Command;

use ApiPlatform\Core\Bridge\Rector\Set\ApiPlatformSetList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RectorCommand extends Command
{
    private const ANNOTATION_TO_ATTRIBUTE_V2 = '@ApiResource to #[ApiResource]';
    private const ANNOTATION_TO_ATTRIBUTE_V3 = '@ApiResource to #[Resource]';
    private const ANNOTATION_TO_ATTRIBUTE_V2_AND_V3 = '@ApiResource to #[ApiResource] and #[Resource]';
    private const ATTRIBUTE_V2_TO_V3 = '#[ApiResource] to #[Resource]';

    protected static $defaultName = 'api:rector:upgrade';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Change ApiResource annotation/attribute to ApiResource/Resource attribute')
            ->addOption('dry-run', '-d', InputOption::VALUE_NONE, 'Rector will show you diff of files that it would change. To make the changes, drop --dry-run')
            ->addArgument('src', InputArgument::REQUIRED, '')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $choice = $io->choice('Choose operation to perform', [
            1 => self::ANNOTATION_TO_ATTRIBUTE_V2,
            self::ANNOTATION_TO_ATTRIBUTE_V3,
            self::ANNOTATION_TO_ATTRIBUTE_V2_AND_V3,
            self::ATTRIBUTE_V2_TO_V3,
        ]);

        $command = 'vendor/bin/rector process '.$input->getArgument('src');

        if ($input->getOption('dry-run')) {
            $command .= ' --dry-run';
        } else {
            $io->confirm('Confirm ?');
        }

        if ($output->isDebug()) {
            $command .= ' --debug';
        }

        switch ($choice) {
            case self::ANNOTATION_TO_ATTRIBUTE_V2;
                $command .= ' --config='.ApiPlatformSetList::ANNOTATION_TO_API_RESOURCE_ATTRIBUTE;
                break;
            case self::ANNOTATION_TO_ATTRIBUTE_V3;
                $command .= ' --config='.ApiPlatformSetList::ANNOTATION_TO_RESOURCE_ATTRIBUTE;
                break;
            case self::ANNOTATION_TO_ATTRIBUTE_V2_AND_V3;
                $command .= ' --config='.ApiPlatformSetList::ANNOTATION_TO_API_RESOURCE_AND_RESOURCE_ATTRIBUTE;
                break;
            case self::ATTRIBUTE_V2_TO_V3;
                $command .= ' --config='.ApiPlatformSetList::ATTRIBUTE_TO_RESOURCE_ATTRIBUTE;
                break;
        }

        $io->title('Run '.$command);
        passthru($command);

        return Command::SUCCESS;
    }
}
