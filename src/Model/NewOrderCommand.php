<?php
namespace App\Model;

use App\Entity\Pricing;
use App\Entity\Team;

class NewOrderCommand {

    public function __construct(
        public ?Team $team = null,
        public ?Pricing $pricing = null,
        public int $qty = 0,
        public ?string $issuer = null,
    )
    {
        
    }

    public function getTotalCost(): string {
        return $this->pricing->getCost() * $this->qty;
    }

    public function getVolume(): int {
        return $this->pricing->getVolume() * $this->qty;
    }
}