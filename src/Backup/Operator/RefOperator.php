<?php

namespace Hawk\Backup\Operator;

class RefOperator implements OperatorInterface
{
    public const OPERATOR = '@';

    public function transform(string $value): mixed
    {
        return $value;
    }

    public function transformReverse(object $value): string
    {
        $reflection = new \ReflectionClass($value);

        if (false !== $reflection->getParentClass()) {
            $reflection = $reflection->getParentClass();
        }

        $class = $reflection->getName();

        $id = $value->getId();

        return static::OPERATOR . $class . '#' . $id;
    }
}
