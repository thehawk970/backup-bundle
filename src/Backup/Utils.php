<?php

namespace Hawk\Backup;

class Utils
{
    public function classToId($class): string
    {
        $x = substr($class, strrpos($class, '\\') + 1);

        return $this->camelCase($x);
    }

    public function camelCase($string): string
    {
        if ('' === $string) {
            return $string;
        }

        $firstLetter = $string[0];
        $firstLetter = strtolower($firstLetter);

        return $firstLetter . substr($string, 1);
    }

    public function propsFormatter(string $props): string
    {
        if (str_contains($props, '_')) {
            $p = explode('_', $props);
            $props = implode('', array_map('ucfirst', $p));
        }

        return $props;
    }

    public function getIdentifier(object $object): string
    {
        $class = $this->getClass($object);
        $id = $object->getId();

        return $class . '#' . $id;
    }

    public function getClass($object): string
    {
        return get_class($object);
    }
}
