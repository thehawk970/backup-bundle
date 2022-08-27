<?php

namespace Hawk\BackupBundle\BackupBundle\Backup\Operator\Operator;

class Operator
{
    protected array $_operator = [];

    public function __construct()
    {
        $this->registerOperator(new RefOperator());
        $this->registerOperator(new AddOperator());
        $this->registerOperator(new DbOperator());
        $this->registerOperator(new DateOperator());
    }

    private function registerOperator(OperatorInterface $param): void
    {
        $this->_operator[$param::OPERATOR] = $param;
    }

    public function charIsOperator($char): bool
    {
        return array_key_exists($char, $this->_operator);
    }

    public function getOperator($id): ?OperatorInterface
    {
        return $this->_operator[$id] ?? null;
    }

    public function transformValue(?string $value): mixed
    {
        if (is_string($value) && '' !== $value) {
            $firstLetter = $value[0];
            $operator = $this->_operator[$firstLetter] ?? false;

            if ($operator instanceof OperatorInterface) {
                return $operator->transform(substr($value, 1));
            }
        }

        return $value;
    }
}
