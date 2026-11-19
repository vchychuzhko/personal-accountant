<?php

namespace App\Entity;

use App\Repository\IncomeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomeRepository::class)]
class Income
{
    public const TYPE_DIVIDEND = 1;

    public const TYPES_MAP = [
        self::TYPE_DIVIDEND => 'Dividend',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'incomes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Balance $balance = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\ManyToOne(inversedBy: 'incomes')]
    private ?Investment $investment = null;

    #[ORM\ManyToOne(inversedBy: 'deposits')]
    private ?Deposit $deposit = null;

    #[ORM\Column(nullable: true)]
    private ?int $type = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getInvestment(): ?Investment
    {
        return $this->investment;
    }

    public function getShare(): ?float
    {
        if (preg_match('/([\d.]+)$/', $this->getName(), $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    public function getPricePerShare(): ?float
    {
        $share = $this->getShare();

        if ($share === null) {
            return null;
        }

        return $this->amount / $share;
    }

    public function setInvestment(?Investment $investment): static
    {
        $this->investment = $investment;

        return $this;
    }

    public function getDeposit(): ?Deposit
    {
        return $this->deposit;
    }

    public function setDeposit(?Deposit $deposit): static
    {
        $this->deposit = $deposit;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function getTypeLabel(): ?string
    {
        return self::TYPES_MAP[$this->getType()] ?? null;
    }

    public function isDividend(): bool
    {
        return $this->getType() === self::TYPE_DIVIDEND;
    }

    public function setType(?int $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function __toString(): string
    {
        $balance = $this->getBalance();

        return $this->getName() . ' - ' . number_format($this->getAmount(), 2, '.' , '') . ' ' . $balance->getCurrency();
    }
}
