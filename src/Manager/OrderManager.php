<?php
namespace App\Manager;

use App\Entity\Order;
use App\Exception\OrderException;
use App\Model\NewOrderCommand;
use App\Model\OrderManagerInterface;
use App\Model\OrderProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;

class OrderManager implements OrderManagerInterface {

    /** @var array<\App\Model\OrderProcessorInterface> */
    private $processors;
    
    public function __construct(
        private EntityManagerInterface $em,
        iterable $processors,
    )
    {
        $this->processors = $processors;
    }

    public function create(NewOrderCommand $command): Order {
        $order = new Order();

        $order->setTeam($command->team);
        $order->setCost($command->getTotalCost());
        $order->setCurrency($command->pricing->getCurrency());
        $order->setVolume($command->getVolume());
        $order->setIssuer($command->issuer);
        $order->setMethod($command->pricing->getMethod());

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    public function validate(Order $order): Order {
        if (!$order->canBeValidated()) {
            throw new OrderException($order, 'this action is not allowed');
        }

        $team = $order->getTeam();
        $volume = $team->getCounter() + $order->getVolume();
        
        $team->setCounter($volume);
        $order->setValidated(true);

        $this->em->persist($team);
        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    public function accept(Order $order): Order
    {
        if (!$order->canBeAccepted()) {
            throw new OrderException($order, 'this action is not allowed');
        }

        $order->setStatus(Order::STATUS_ACCEPTED);
        $order->setClosedAt(new \DateTimeImmutable('now'));
        
        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    public function refuse(Order $order, ?string $note = null): Order
    {
        if (!$order->canBeRefused()) {
            throw new OrderException($order, 'this action is not allowed');
        }
        
        $order->setStatus(Order::STATUS_REFUSED);
        $order->setNote($note);
        $order->setClosedAt(new \DateTimeImmutable('now'));
        
        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    public function waiting(Order $order, ?string $note = null): Order
    {
        if (Order::STATUS_PENDING != $order->getStatus()) {
            throw new OrderException($order, 'this action is not allowed');
        }
        
        $order->setStatus(Order::STATUS_WAITING);
        $order->setNote($note);
        
        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    public function process(Order $order): Order {
        if (!$order->isPending()) {
            throw new OrderException($order, 'this action is not allowed');
        }

        $processor = $this->findProcessor($order);

        if (null == $processor) {
            throw new OrderException($order, 'system not able to process this order');
        }

        return $processor->process($order, $this);
    }

    private function findProcessor(Order $order): ?OrderProcessorInterface {
        $processor = null;
        foreach ($this->processors as $p) {
            if ($p->support($order)) {
                return $p;
            }
        }

        return $processor;
    }
}