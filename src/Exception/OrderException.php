<?php
namespace App\Exception;

use App\Entity\Order;

class OrderException extends \Exception {

    public function __construct(private Order $order, string $message)
    {
        parent::__construct($message);
    }

    /**
     * Get the value of order
     */ 
    public function getOrder():  Order
    {
        return $this->order;
    }
}