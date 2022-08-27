<?php

namespace Hawk\BackupBundle\BackupBundle\Backup\Read;

use Doctrine\ORM\EntityManagerInterface;
use Hawk\BackupBundle\BackupBundle\Backup\Log;
use Hawk\BackupBundle\BackupBundle\Backup\Operator\Operator\Operator;
use Hawk\BackupBundle\BackupBundle\Backup\Operator\Operator\RefOperator;
use Hawk\BackupBundle\BackupBundle\Backup\Storage\EntityStack;
use Hawk\BackupBundle\BackupBundle\Backup\Storage\PreFetch;
use Hawk\BackupBundle\BackupBundle\Backup\Storage\Stack;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class Reader
{
    protected array $fetch = [];
    protected array $entities = [];
    private Log $console;
    private string $folder;

    public function __construct(
        protected Operator               $operator,
        protected EntityManagerInterface $entityManager,
        protected EntityStack            $entityStack,
        protected Stack                  $stack,
        protected PreFetch               $preFetch,
    )
    {
    }

    public function useOutput(OutputInterface $output): static
    {
        $this->console = new  Log($output);

        return $this;
    }

    public function folder(string $folder): static
    {
        $this->folder = $folder;

        return $this;
    }

    public function yml(string $name): static
    {
        $root = dirname(__DIR__, 3);
        $uri = implode(DIRECTORY_SEPARATOR, [$root, $this->folder, $name]);
        $data = Yaml::parseFile($uri);

        $this->console->info('read : ' . $uri);

        foreach ($data as $className => $array) {
            foreach ($array as $id => $value) {
                $this->preFetch->set($className . '#' . $id, $value);
            }
        }

        return $this;
    }

    public function all(): static
    {
        foreach ($this->preFetch->keys() as $id) {
            $this->get($id);
        }

        return $this;
    }

    public function get($key)
    {
        if (false === $this->stack->has($key)) {
            $this->console->debug('LOAD : ' . $key);
            [$class, $id] = explode('#', $key);
            $data = $this->preFetch->get($key);

            $object = new $class();
            $this->stack->set($key, $object);
            $this->fill($key, $object, $data);
        }


        return $this->stack->get($key);
    }

    private function fill(string $id, object $instance, mixed $items): void
    {
        $reflection = new \ReflectionClass($instance);
        $priority = 100;

        foreach ($items as $name => $value) {
            if (is_array($value)) {
                $method = $this->fillProperty('add', $name, $reflection);
                if (false === $method) {
                    $method = $this->fillProperty('set', $name, $reflection);
                }

                foreach ($value as $v) {
                    $this->injectValue($instance, $method, $v, $priority);
                }
            } else {
                $method = $this->fillProperty('set', $name, $reflection);
                if (false === $method) {
                    $method = $this->fillProperty('add', $name, $reflection);
                }

                $this->injectValue($instance, $method, $value, $priority);
            }
        }
        $this->stack->set($id, $instance);
        $this->entityStack->insert($instance, $priority);
    }

    private function fillProperty($prefix, $name, \ReflectionClass $reflectionClass): \ReflectionMethod|false
    {
        $fn = strtolower($prefix) . ucfirst($name);

        if (true === $reflectionClass->hasMethod($fn)) {
            return $reflectionClass->getMethod($fn);
        }

        // Try alt name without s
        $fn_alt = substr($fn, 0, -1);
        if (true === $reflectionClass->hasMethod($fn_alt)) {
            return $reflectionClass->getMethod($fn_alt);
        }

        return false;
    }

    private function injectValue(object $instance, \ReflectionMethod $method, $value, &$priority = 100): void
    {
        if ('' === $value) {
            $parameter = $method->getParameters()[0];
            if ($parameter instanceof \ReflectionParameter) {
                if ('DateTimeInterface' === $parameter->getType()?->getName() || 'DateTime' === $parameter->getType()?->getName()) {
                    if ($parameter->getType()?->allowsNull()) {
                        $value = null;
                    } else {
                        $value = new \DateTime();
                    }
                }

                if ('int' === $parameter->getType()?->getName()) {
                    $value = 0;
                }

                if ('float' === $parameter->getType()?->getName()) {
                    $value = 0.0;
                }
            }
        }

        if (is_string($value) && '' !== $value) {
            $firstLetter = $value[0];

            if ($this->operator->charIsOperator($firstLetter) === true) {
                $value = $this->operator->transformValue($value);
                if ($this->operator->getOperator($firstLetter) instanceof RefOperator) {
                    $value = $this->get($value);
                }
            }

            --$priority;
        }

        $this->inject($instance, $method, $value);
    }

    private function inject(object $instance, \ReflectionMethod $method, $value): void
    {
        $method->invoke($instance, $value);
    }

    public function persist()
    {
        $this->entityManager->beginTransaction();
        foreach ($this->entityStack as $k => $value) {
            $this->entityManager->persist($value);
        }
        $this->entityManager->commit();

        $this->entityManager->flush();
        $this->console->debug('WRITE IN DB');
    }


}