<?php

namespace Hawk\Backup\Operator;

use DateTimeInterface;

class DateOperator implements OperatorInterface
{
    public const OPERATOR = '*';

    public function setOperator(Operator $operator)
    {
        $this->operator = $operator;
    }

    public function transform(string $value): mixed
    {
        return \DateTime::createFromFormat(DateTimeInterface::ATOM, $value);
    }

    public function transformReverse(object $value): ?string
    {
        if ($value instanceof \DateTime) {
            return static::OPERATOR . $value->format(DateTimeInterface::ATOM);
        }

        return null;
    }
}
