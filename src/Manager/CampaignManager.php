<?php
namespace App\Manager;

use App\Entity\Campaign;
use App\Exception\CampaignConfigurationException;
use App\Exception\CampaignException;
use App\Model\CampaignTrackerInterface;
use Doctrine\ORM\EntityManagerInterface;

class CampaignManager implements CampaignTrackerInterface {

    /** @var array<\App\Model\CampaignProcessorInterface> */
    private $processors;

    private ?string $url = null;

    public function __construct(
        private EntityManagerInterface $em,
        iterable $processors,
        string $sender_url
    )
    {
        $this->processors = \iterator_to_array($processors);
        $this->url = $sender_url;
    }

    public function process(Campaign $campaign, ?string $preferredUrl = null): Campaign {

        if (!$campaign->isCostAffordable()) {
            throw new CampaignException($campaign, 'insufficient balance. Please recharge your account');
        }
        
        $url = $preferredUrl ?? $this->url;

        if (!$url) {
            throw new CampaignConfigurationException('campaign URL configuration missing');
        }

        \reset($this->processors);

        while (!is_null(key($this->processors))) {
            /** @var \App\Model\CampaignProcessorInterface  */
            $processor = \current($this->processors);

            if ($processor->support($url)) {
                $campaign->setProvider($processor->getId());

                $this->em->persist($campaign);
                $this->em->flush();

                return $processor->process($campaign, $this);
            }
            
            \next($this->processors);
        }

        throw new CampaignConfigurationException(sprintf('No processor available for the URL "%s"', $this->url));
    }

    public function sent(Campaign $campaign, ?string $trackingId = null, ?string $notes = null): Campaign
    {
        if (Campaign::STATUS_DRAFT !== $campaign->getStatus()) {
            throw new CampaignException($campaign, 'this operation is not allowed');
        }

        $campaign->setStatus(Campaign::STATUS_SENT);
        $campaign->setTrackingId($trackingId);
        $campaign->setNotes($notes);
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    public function pending(Campaign $campaign, ?string $trackingId = null, ?string $notes = null): Campaign
    {
        if (Campaign::STATUS_DRAFT !== $campaign->getStatus()) {
            throw new CampaignException($campaign, 'this operation is not allowed');
        }

        $campaign->setStatus(Campaign::STATUS_PENDING);
        $this->em->persist($campaign);
        $campaign->setTrackingId($trackingId);
        $campaign->setNotes($notes);
        $this->em->flush();

        return $campaign;
    }

    public function failed(Campaign $campaign, ?string $trackingId = null, ?string $notes = null): Campaign
    {
        if (Campaign::STATUS_DRAFT != $campaign->getStatus() && Campaign::STATUS_PENDING != $campaign->getStatus()) {
            throw new CampaignException($campaign, 'this operation is not allowed');
        }

        $campaign->setStatus(Campaign::STATUS_FAILED);
        $this->em->persist($campaign);
        $campaign->setTrackingId($trackingId);
        $campaign->setNotes($notes);
        $this->em->flush();

        return $campaign;
    }

    /**
     * Get the value of url
     */ 
    public function getUrl(): ?string
    {
        return $this->url;
    }
}