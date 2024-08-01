<?php
namespace App\Model;

use App\Entity\Order;

interface OrderManagerInterface {

    function accept(Order $order): Order;
    function refuse(Order $order, ?string $note = null): Order;
    function waiting(Order $order, ?string $note = null): Order;
}