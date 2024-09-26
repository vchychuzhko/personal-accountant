<?php

namespace App\Entity;

use App\Repository\DepositRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepositRepository::class)]
class Deposit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'deposits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Currency $currency = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column]
    private ?float $interest = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $end_date = null;

    #[ORM\Column]
    private ?int $period = null;

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

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmountInUsd(): ?float
    {
        $currency = $this->getCurrency();

        return $this->getAmount() / $currency->getRate();
    }

    public function getExpectedProfit(): ?float
    {
        $interest = $this->getInterest();
        $period = $this->getPeriod();

        return $this->getAmount() * ($interest / 100 * ($period / 12));
    }

    public function getExpectedProfitInUsd(): ?float
    {
        $currency = $this->getCurrency();

        return $this->getExpectedProfit() / $currency->getRate();
    }

    public function getExpectedTotal(): ?float
    {
        $interest = $this->getInterest();
        $period = $this->getPeriod();

        return $this->getAmount() + $this->getAmount() * ($interest / 100 * ($period / 12));
    }

    public function getExpectedTotalInUsd(): ?float
    {
        $currency = $this->getCurrency();

        return $this->getExpectedTotal() / $currency->getRate();
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

    public function setEndDate(\DateTimeInterface $end_date): static
    {
        $this->end_date = $end_date;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName() . ' (' . $this->getCurrency() . ')';
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
}
