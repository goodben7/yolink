<?php

namespace App\Entity;

use App\Repository\CampaignTrackingInfoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampaignTrackingInfoRepository::class)]
class CampaignTrackingInfo
{
    const STATUS_QUEUED = 'Q';
    const STATUS_SENT = 'S';
    const STATUS_UNSET = 'U';
    const STATUS_FAILED = 'F';
    const STATUS_DELIVERED = 'D';
    const STATUS_UNDELIVERED = 'R';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trackingInfos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campaign $campaign = null;

    #[ORM\Column(length: 15)]
    private ?string $recipient = null;

    #[ORM\Column(length: 1)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackingId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): static
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(string $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTrackingId(): ?string
    {
        return $this->trackingId;
    }

    public function setTrackingId(?string $trackingId): static
    {
        $this->trackingId = $trackingId;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
