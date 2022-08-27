<?php

namespace Hawk\BackupBundle\BackupBundle\Backup\Storage;

class ArrayStorage
{
    protected array $store = [];

    public function set($id, $data, $subId = null): void
    {
        if (null !== $subId) {
            $this->store[$id][$subId] = $data;
        } else {
            $this->store[$id] = $data;
        }
    }

    public function setData($data = []): void
    {
        $this->store = $data;
    }

    public function get($id): mixed
    {
        return $this->store[$id] ?? null;
    }

    public function has($id): bool
    {
        return array_key_exists($id, $this->store);
    }

    public function all(): array
    {
        return $this->store;
    }

    public function keys(): array
    {
        return array_keys($this->store);
    }
}
