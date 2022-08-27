<?php

namespace Hawk\Backup;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class Log
{
    public function __construct(public OutputInterface $output)
    {
        $this->output->getFormatter()->setStyle('fire', new OutputFormatterStyle('white', 'blue', ['bold']));
        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('white', 'green', ['bold']));
    }

    public function debug($message): void
    {
        $this->output->writeln('<fire>' . $message . '</fire>', OutputInterface::VERBOSITY_DEBUG);
    }

    public function info($message): void
    {
        $this->output->writeln('<info>' . $message . '</info>', OutputInterface::VERBOSITY_NORMAL);
    }


}