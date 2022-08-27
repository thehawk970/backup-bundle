<?php

namespace Hawk\BackupBundle\BackupBundle\Backup\Save;

use Doctrine\ORM\EntityManagerInterface;
use Hawk\BackupBundle\BackupBundle\Backup\Queue;

class Save
{

    protected array $parsed = [];
    protected array $stack = [];
    private Queue $queue;
    private Queue $entityQueue;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected Entity                 $entity
    )
    {
        $this->queue = new Queue();
        $this->entityQueue = new Queue();
        $this->entity->setQueue($this->entityQueue);
    }

    public function load(array $defaultEntities = []): void
    {
        $this->queue->setData($defaultEntities);

        while ($this->queue->length() > 0) {
            $ns = $this->queue->dequeue();
            $repository = $this->entityManager->getRepository($ns);

            foreach ($repository->findAll() as $e) {
                $this->readEntity($ns, $e);
            }
        }

        while ($this->entityQueue->length() > 0) {
            [$ns, $id] = explode('#', $this->entityQueue->dequeue());
            $e = $this->entityManager->getRepository($ns)->find($id);
            $this->readEntity($ns, $e);
        }
    }

    public function readEntity($ns, object $e): mixed
    {

        $className = $this->entity->getUtils()->getClass($e);
        if (str_starts_with($className, 'Proxies\\')) {
            $e->__load(); // Force load
        }

        $id = $className . '#' . $e->getId();

        if (in_array($id, $this->parsed) === false) {
            $properties = $this->entity->read($e);
            $this->stack[$ns][$e->getId()] = $properties;
            $this->parsed[] = $id;
        }

        return $this->stack[$ns][$e->getId()];
    }

    public function getStack(): array
    {
        return $this->stack;
    }


}