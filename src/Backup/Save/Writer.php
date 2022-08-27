<?php

namespace Hawk\BackupBundle\BackupBundle\Backup\Save;

use Hawk\BackupBundle\BackupBundle\Backup\Log;
use Hawk\BackupBundle\BackupBundle\Backup\Utils;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class Writer
{
    public Log $console;
    protected string $folder = 'var/backup';
    protected string $name = 'save.yml';
    protected array $stack = [];
    protected array $output = [];
    protected array $remplacement = [];

    public function __construct(
        protected Utils $utils
    )
    {

    }

    public function useOutput(OutputInterface $output)
    {
        $this->console = new  Log($output);

        return $this;
    }

    public function folder($folder): static
    {
        $this->folder = $folder;

        return $this;
    }

    public function in(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function stack(array $getStack): static
    {
        $this->stack = array_merge($this->stack, $getStack);

        return $this;
    }

    public function write(bool $dryRun = false): string
    {
        $this->preFetch();
        $this->performReplacement();
        return $this->performWrite($dryRun);
    }

    protected function preFetch(): void
    {
        foreach ($this->stack as $ns => $item) {
            foreach ($item as $id => $object) {
                $this->output[$ns][] = $object;
                $real_id = count($this->output[$ns]) - 1;
                $this->remplacement['@' . $ns . '#' . $id] = '@' . $ns . '#' . $real_id;

                $this->console->debug(sprintf('Fetch : %s # %s ', $ns, $id));
            }
        }
    }

    protected function performReplacement(): void
    {
        foreach ($this->output as &$item) {
            foreach ($item as &$object) {
                foreach ($object as $name => &$value) {
                    if (is_array($value)) {
                        foreach ($value as &$x) {
                            if (is_string($x) && array_key_exists($x, $this->remplacement)) {
                                $x = $this->remplacement[$x];
                            }
                        }
                    }
                    if (is_string($value) && array_key_exists($value, $this->remplacement)) {
                        $value = $this->remplacement[$value];
                    }
                }
            }
        }
    }

    protected function performWrite(bool $dryRun): string
    {
        $date = new \DateTime();
        $name = sprintf($this->name, $date->format('Ymdhis'));

        $root = dirname(__DIR__, 3);

        $uri = implode(DIRECTORY_SEPARATOR, [$root, $this->folder, $name]);

        if ($dryRun === false) {
            $yml = Yaml::dump($this->output);
            file_put_contents($uri, $yml);
        }


        return $uri;
    }
}