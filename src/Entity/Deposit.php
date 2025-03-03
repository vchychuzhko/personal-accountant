<?php

namespace App\Entity;

use App\Repository\DepositRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepositRepository::class)]
class Deposit
{
    public const STATUS_ACTIVE = 0;
    public const STATUS_COMPLETED = 1;

    public const STATUS_MAP = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_COMPLETED => 'Closed',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'deposits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Balance $balance = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column(nullable: true)]
    private ?float $profit = null;

    #[ORM\Column]
    private ?int $status = null;

    #[ORM\Column]
    private ?float $interest = null;

    #[ORM\Column(nullable: true)]
    private ?float $tax = null;

    #[ORM\Column]
    private ?int $period = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $end_date = null;

    public function __construct()
    {
        $this->status = self::STATUS_ACTIVE;
        $this->start_date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getBalance(): ?Balance
    {
        return $this->balance;
    }

    public function setBalance(?Balance $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getAmountInUsd(): ?float
    {
        $balance = $this->getBalance();
        $currency = $balance->getCurrency();

        return $this->getAmount() / $currency->getRate();
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getExpectedProfit(): ?float
    {
        $interest = $this->getInterest();
        $tax = $this->getTax();
        $period = $this->getPeriod();

        return $this->getAmount() * ($interest / 100 * ($period / 12)) * (1 - $tax / 100);
    }

    public function getExpectedProfitInUsd(): ?float
    {
        $balance = $this->getBalance();
        $currency = $balance->getCurrency();

        return $this->getExpectedProfit() / $currency->getRate();
    }

    public function getProfit(): ?float
    {
        return $this->profit;
    }

    public function setProfit(float $profit): static
    {
        $this->profit = $profit;

        return $this;
    }

    public function getProfitInUsd(): ?float
    {
        $balance = $this->getBalance();
        $currency = $balance->getCurrency();

        return $this->getProfit() ? $this->getProfit() / $currency->getRate() : null;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getStatusLabel(): ?string
    {
        return self::STATUS_MAP[$this->getStatus()] ?? null;
    }

    public function isActive(): bool
    {
        return $this->getStatus() === self::STATUS_ACTIVE;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getInterest(): ?float
    {
        return $this->interest;
    }

    public function setInterest(float $interest): static
    {
        $this->interest = $interest;

        return $this;
    }

    public function getTax(): ?float
    {
        return $this->tax;
    }

    public function setTax(float $tax): static
    {
        $this->tax = $tax;

        return $this;
    }

    public function getPeriod(): ?int
    {
        return $this->period;
    }

    public function setPeriod(int $period): static
    {
        $this->period = $period;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(\DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function getEndDateFormatted(): ?string
    {
        return $this->getEndDate() ? $this->getEndDate()->format('Y-m-d') : null;
    }

    public function setEndDate(\DateTimeInterface $end_date): static
    {
        $this->end_date = $end_date;

        return $this;
    }

    public function __toString(): string
    {
        $balance = $this->getBalance();
        $currency = $balance->getCurrency();

        return $this->getName() . ' (' . $currency . ')';
    }
}
