<?php

namespace Hawk\Backup;

class Queue
{
    private mixed $data = [];

    public function __construct($data = [])
    {
        $this->setData($data);
    }

    public function setData(mixed $data)
    {
        $this->data = $data;
    }

    public function length()
    {
        return count($this->data);
    }

    public function before($refItem, $item): void
    {

        if ($this->key($refItem) === null) {
            $this->first($item);
        }

        $this->insert($this->key($refItem), $item);
    }

    public function key($item)
    {
        if ($this->has($item) === true) {
            return array_search($item, $this->data);
        }

        return null;
    }

    public function has($item)
    {
        return in_array($item, $this->data);
    }

    public function first($item)
    {
        array_unshift($this->data, $item);
    }

    protected function insert($id, $item): void
    {
        array_splice($this->data, $id, 0, $item);
    }

    public function after($refItem, $item): void
    {

        if ($this->key($refItem) === null) {
            $this->last($item);
        }

        $this->insert($this->key($refItem) + 1, $item);
    }

    public function last($item)
    {
        $this->data[] = $item;
    }

    public function enqueue($item): void
    {
        $this->last($item);
    }

    public function dequeue(): mixed
    {
        return array_pop($this->data);
    }

    public function append($items): void
    {
        $this->data[] = $items;
    }

}