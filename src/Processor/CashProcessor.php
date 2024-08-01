<?php
namespace App\Processor;

use App\Entity\Order;
use App\Model\OrderManagerInterface;
use App\Model\OrderProcessorInterface;

class CashProcessor implements OrderProcessorInterface {

    public function support(Order $order): bool
    {
        return $order->getMethod() === Order::METHOD_CASH;
    }

    public function process(Order $order, OrderManagerInterface $manager): Order
    {
        return $manager->accept($order);
    }
}