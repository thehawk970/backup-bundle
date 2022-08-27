<?php

namespace Hawk\BackupBundle\BackupBundle\Backup\Operator\Operator;

interface OperatorInterface
{
    public function transform(string $value): mixed;

    public function transformReverse(object $value): ?string;
}
