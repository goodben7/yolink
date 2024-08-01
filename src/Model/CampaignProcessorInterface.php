<?php
namespace App\Model;

use App\Entity\Campaign;

interface CampaignProcessorInterface {

    function getId(): string;
    function support(string $url): bool;
    function process(Campaign $campaign, CampaignTrackerInterface $tracker): Campaign;
}