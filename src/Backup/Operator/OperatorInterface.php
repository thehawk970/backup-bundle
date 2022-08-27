<?php

namespace Hawk\Backup\Operator;

interface OperatorInterface
{
    public function transform(string $value): mixed;

    public function transformReverse(object $value): ?string;
}
