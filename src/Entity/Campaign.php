<?php

namespace App\Entity;

use App\Model\NewCampaignModel;
use App\Repository\CampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Index(fields: ['trackingId'])]
#[ORM\Entity(repositoryClass: CampaignRepository::class)]
class Campaign
{
    const STATUS_DRAFT = 'D';
    const STATUS_SENT = 'S';
    const STATUS_PENDING = 'P';
    const STATUS_FAILED = 'F';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(length: 1)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\ManyToOne(inversedBy: 'campaigns')]
    private ?Team $team = null;

    #[ORM\Column]
    private array $contact = [];

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $provider = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackingId = null;

    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: CampaignTrackingInfo::class, orphanRemoval: true)]
    private Collection $trackingInfos;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->trackingInfos = new ArrayCollection();
        $this->status = self::STATUS_DRAFT;
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public static function getAvailablesStatus(): array 
    {
        return [
            "Brouillon" => self::STATUS_DRAFT,
            "Envoyé" => self:: STATUS_SENT,
            "Transféré" => self:: STATUS_PENDING,
            "Echoué" => self::STATUS_FAILED,
        ];
    }


    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }    

    /**
     * Get the value of contact
     */ 
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set the value of contact
     *
     * @return  self
     */ 
    public function setContact($contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get the value of status
     */ 
    public function getStatus()
    {
        return $this->status;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): static
    {
        $this->provider = $provider;

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

    /**
     * @return Collection<int, CampaignTrackingInfo>
     */
    public function getTrackingInfos(): Collection
    {
        return $this->trackingInfos;
    }

    public function addTrackingInfo(CampaignTrackingInfo $trackingInfo): static
    {
        if (!$this->trackingInfos->contains($trackingInfo)) {
            $this->trackingInfos->add($trackingInfo);
            $trackingInfo->setCampaign($this);
        }

        return $this;
    }

    public function removeTrackingInfo(CampaignTrackingInfo $trackingInfo): static
    {
        if ($this->trackingInfos->removeElement($trackingInfo)) {
            // set the owning side to null (unless already changed)
            if ($trackingInfo->getCampaign() === $this) {
                $trackingInfo->setCampaign(null);
            }
        }

        return $this;
    }

    public function getCost(): int {
        $d = strlen($this->message) / 160;
        $n = intval($d);
        $n += $d > $n ? 1: 0;

        return $n * count($this->contact);
    }

    public function isCostAffordable(): bool {
        
        
        return $this->getCost() <= $this->team->getCounter();
    }

    public static function fromModel(NewCampaignModel $model): static {
        $c = new self;
        $c->message = $model->getMessage();
        $c->contact = explode(',', $model->getRecipients());
        return $c;
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
