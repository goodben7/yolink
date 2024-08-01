<?php

namespace App\Entity;

use App\Model\NewOrderModel;
use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Index(fields: ['issuer'])]
#[ORM\Index(fields: ['date'])]
#[ORM\Index(fields: ['txReference'])]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    const STATUS_PENDING = 'P';
    const STATUS_ACCEPTED = 'A';
    const STATUS_WAITING = 'W';
    const STATUS_REFUSED = 'R';

    const METHOD_CASH = 'C';
    const METHOD_EMONEY = 'E';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Team $team = null;

    #[ORM\Column]
    private ?int $volume = null;

    #[ORM\Column(length: 1)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $cost = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $txReference = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $issuer = null;

    #[ORM\Column(length: 1)]
    private ?string $method = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $validated = null;

    public function __construct()
    {
        $this->status = self::STATUS_PENDING;
        $this->date = new \DateTimeImmutable('now');
        $this->validated = false;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getVolume(): ?int
    {
        return $this->volume;
    }

    public function setVolume(int $volume): static
    {
        $this->volume = $volume;

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

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): static
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(string $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getTxReference(): ?string
    {
        return $this->txReference;
    }

    public function setTxReference(?string $txReference): static
    {
        $this->txReference = $txReference;

        return $this;
    }

    public function getIssuer(): ?string
    {
        return $this->issuer;
    }

    public function setIssuer(?string $issuer): static
    {
        $this->issuer = $issuer;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public static function getAvailableStatus(): array {
        return [
            'En attente' => self::STATUS_PENDING,
            'En cours' => self::STATUS_WAITING,
            'Accepté' => self::STATUS_ACCEPTED,
            'Réfusé' => self::STATUS_REFUSED,
        ];
    }

    public static function getAvailableMethods(): array {
        return [
            'Paiement Cash' => self::METHOD_CASH,
            'Paiement e-Money' => self::METHOD_EMONEY,
        ];
    }

    public static function getMethodLabel(string $method): mixed {
        return array_search($method, self::getAvailableMethods());
    }

    public function canBeAccepted(): bool {
        return in_array($this->status, [self::STATUS_WAITING, self::STATUS_PENDING]);
    }

    public function canBeRefused(): bool {
        return in_array($this->status, [self::STATUS_WAITING, self::STATUS_PENDING]);
    }
    
    public function canBeValidated(): bool {
        return self::STATUS_ACCEPTED == $this->status && !$this->validated;
    }

    public function isPending(): bool {
        return self::STATUS_PENDING === $this->status;
    }

    public function isValidated(): ?bool
    {
        return $this->validated;
    }

    public function setValidated(bool $validated): static
    {
        $this->validated = $validated;

        return $this;
    }
}
