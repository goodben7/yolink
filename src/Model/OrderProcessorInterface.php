<?php
namespace App\Model;

use App\Entity\Order;

interface OrderProcessorInterface {

    function process(Order $order, OrderManagerInterface $manager): Order;
    function support(Order $order): bool;
}