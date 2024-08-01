<?php
namespace App\Model;

use App\Entity\Campaign;

interface CampaignTrackerInterface {

    function failed(Campaign $campaign, ?string $trackingId = null, ?string $notes = null): Campaign;
    function pending(Campaign $campaign, ?string $trackingId = null, ?string $notes = null): Campaign;
    function sent(Campaign $campaign, ?string $trackingId = null, ?string $notes = null): Campaign;
    function getUrl(): ?string;
}