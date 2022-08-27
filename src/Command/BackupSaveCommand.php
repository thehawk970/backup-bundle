<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Hawk\Backup\Save\Save;
use Hawk\Backup\Save\Writer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
    name: 'backup:save',
    description: 'Save exported db in file'
)]
class BackupSaveCommand extends Command
{
    public function __construct(protected Save $save, protected Writer $writer, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption('dir', 'd', InputOption::VALUE_OPTIONAL, 'file path of base')
            ->addOption('ns', null, InputOption::VALUE_OPTIONAL, 'dir path of base')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'dry run');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $input->getOption('dir') ?? 'var/backup';;
        $ns = $input->getOption('ns') ?? 'save';
        $dryRun = $input->getOption('dry-run') ?? false;


        $this->save->load([]);
        $uri = $this->writer
            ->useOutput($output)
            ->folder($dir)
            ->in($ns . '.%s.yml')
            ->stack($this->save->getStack())
            ->write($dryRun);

        $this->writer->console->info("<success>Write in file : ${uri}</success>");

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
