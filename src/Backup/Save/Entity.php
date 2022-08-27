<?php

namespace Hawk\BackupBundle\BackupBundle\Backup\Save;

use Doctrine\ORM\PersistentCollection;
use Hawk\BackupBundle\BackupBundle\Backup\Operator\Operator\DateOperator;
use Hawk\BackupBundle\BackupBundle\Backup\Operator\Operator\Operator;
use Hawk\BackupBundle\BackupBundle\Backup\Operator\Operator\RefOperator;
use Hawk\BackupBundle\BackupBundle\Backup\Queue;
use Hawk\BackupBundle\BackupBundle\Backup\Utils;
use function PHPUnit\Framework\equalTo;

class Entity
{
    private Queue $queue;
    private $ignore = [];

    public function __construct(
        protected Utils    $utils,
        protected Operator $operator
    )
    {

    }

    public function getUtils(): Utils
    {
        return $this->utils;
    }

    public function read(mixed $object): array
    {
        $properties = [];
        $reflection = new \ReflectionClass($object);
        if (false !== $reflection->getParentClass()) {
            $reflection = $reflection->getParentClass();
        }

        $this->readProperties($properties, $reflection, $object);
        $this->readMethods($properties, $reflection, $object);

        return $properties;
    }

    private function readProperties(&$properties, \ReflectionClass $reflectionClass, object $object)
    {
        foreach ($reflectionClass->getProperties() as $property) {
            if ('id' !== $property->getName()) {

                $attr = $property->getAttributes('Vich\UploaderBundle\Mapping\Annotation\UploadableField');
                if (count($attr) > 0) {
                    $this->ignore[] = $property->getName();
                }

                $get = 'get' . ucfirst($property->getName());
                $set = 'set' . ucfirst($property->getName());

                if ($reflectionClass->hasMethod($get) === true && $reflectionClass->hasMethod($set)) {
                    $this->_method($property->getName(), $properties, $reflectionClass->getMethod($get), $object);
                }
            }
        }

    }

    private function _method($name, &$properties, \ReflectionMethod $reflectionMethod, object $object): void
    {
        $props = $this->utils->camelCase($name);
        if (array_key_exists($props, $properties) === false) {
            $items = $this->getValue($reflectionMethod, $object);

            $this->stockProperties($properties, $props, $items);
        }
    }

    public function getValue(\ReflectionMethod $reflectionMethod, object $object): mixed
    {
        $value = $reflectionMethod->invoke($object);

        if (is_array($value)) {
            $value = $this->parseValueArray($value);
        } elseif ($value instanceof \DateTime) {
            $value = $this->parseValueDatetime($value);
        } elseif ($value instanceof PersistentCollection) {
            $value = $this->parseValueCollection($value);
        } elseif (is_object($value)) {
            $value = $this->parseValueObject($value);
        }

        return $value;
    }

    private function parseValueArray(array $value): array
    {
        $x = [];

        foreach ($value as $v) {
            if (is_object($v)) {
                $x[] = $this->parseValueObject($v);
            } else {
                $x[] = $v;
            }
        }

        return $x;
    }

    private function parseValueObject(object $object): string
    {
        $operator = $this->operator->getOperator(RefOperator::OPERATOR);
        return $this->pushInStack($operator?->transformReverse($object));
    }

    private function pushInStack(?string $id)
    {
        if ($this->operator->charIsOperator($id[0]) === true) {
            $this->queue->append(substr($id, 1));
        }

        return $id;
    }

    private function parseValueDatetime(\DateTime $value): string
    {
        return $this->operator->getOperator(DateOperator::OPERATOR)?->transformReverse($value);
    }

    private function parseValueCollection(PersistentCollection $object): array
    {
        $collection = [];

        foreach ($object as $value) {
            $id = $this->operator->getOperator(RefOperator::OPERATOR)?->transformReverse($value);
            $collection[] = $this->pushInStack($id);
        }

        return $collection;
    }

    public function stockProperties(&$property, $name, $value): void
    {
        if (array_key_exists($name, $property) === false && in_array($name, $this->ignore) === false) {
            $property[$name] = $value;
        }
    }

    private function readMethods(&$properties, \ReflectionClass $reflectionClass, object $object): void
    {

        foreach ($reflectionClass->getMethods() as $method) {
            if ($method->getName() !== 'getId' && str_starts_with($method->getName(), 'get')) {

                $prefix = substr($method->getName(), 0, 3);
                $check = ($prefix === 'get') ? 'set' : 'get';

                $property = substr($method->getName(), 3);
                if ($reflectionClass->hasMethod($check . $property) === true) {
                    $this->_method($property, $properties, $method, $object);
                }
            }
        }
    }

    public function setQueue(Queue $entityQueue)
    {
        $this->queue = $entityQueue;
    }
}