<?php
namespace App\Exception;

use App\Entity\Campaign;

class CampaignException extends \Exception {

    public function __construct(private Campaign $campaign, string $message)
    {
        parent::__construct($message);
    }

    /**
     * Get the value of campaign
     */ 
    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }
}