<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hawk\BackupBundle\Command;

use Hawk\BackupBundle\BackupBundle\Backup\Read\Reader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * A console command that creates users and stores them in the database.
 *
 * To use this command, open a terminal window, enter into your project
 * directory and execute the following:
 *
 *     $ php bin/console app:add-user
 *
 * To output detailed information, increase the command verbosity:
 *
 *     $ php bin/console app:add-user -vv
 *
 * See https://symfony.com/doc/current/console.html
 *
 * We use the default services.yaml configuration, so command classes are registered as services.
 * See https://symfony.com/doc/current/console/commands_as_services.html
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
#[AsCommand(
    name: 'backup:import',
    description: 'Import yml file in DB'
)]
class BackupImportCommand extends Command
{
    public function __construct(protected Reader $reader, string $name = null)
    {
        parent::__construct($name);
    }


    protected function configure(): void
    {
        $this
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'file path of base')
            ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'dir path of base')
            ->addOption('last', null, InputOption::VALUE_NONE, 'Use last record file')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'dry run');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $file = null;
        $dir = $input->getOption('dir') ?? 'var/backup';
        $dryRun = $input->getOption('dry-run') ?? false;

        if ($input->hasOption('file') === true && ($file = $input->getOption('file')) !== null) {
            $file = $input->getOption('file');
            ['dirname' => $dir, 'basename' => $file] = pathinfo($file);
        } else {
            $input->setOption('last', true);
        }

        if ($input->getOption('last') === true) {
            $finder = new Finder();
            $files = $finder
                ->in($dir)
                ->sortByName()
                ->reverseSorting()
                ->files();

            if ($files->count() > 0) {
                $last = array_keys(iterator_to_array($files->getIterator(), true))[0];
                ['dirname' => $dir, 'basename' => $file] = pathinfo($last);
            }
        }

        if ($file === null) {

            $output->writeln('<error>[ERROR]  File not defined</error>');

            return Command::FAILURE;
        }

        $this->reader
            ->useOutput($output)
            ->folder($dir)
            ->yml($file)
            ->all();

        if ($dryRun === false) {
            $this->reader->persist();
        }

        return Command::SUCCESS;
    }


    /**
     * The command help is usually included in the configure() method, but when
     * it's too long, it's better to define a separate method to maintain the
     * code readability.
     */
    private function getCommandHelp(): string
    {
        return <<<'HELP'
            The <info>%command.name%</info> command creates new users and saves them in the database:

              <info>php %command.full_name%</info> <comment>username password email</comment>

            By default the command creates regular users. To create administrator users,
            add the <comment>--admin</comment> option:

              <info>php %command.full_name%</info> username password email <comment>--admin</comment>

            If you omit any of the three required arguments, the command will ask you to
            provide the missing values:

              # command will ask you for the email
              <info>php %command.full_name%</info> <comment>username password</comment>

              # command will ask you for the email and password
              <info>php %command.full_name%</info> <comment>username</comment>

              # command will ask you for all arguments
              <info>php %command.full_name%</info>
            HELP;
    }
}
