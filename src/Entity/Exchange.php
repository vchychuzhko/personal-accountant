<?php

namespace App\Entity;

use App\Repository\ExchangeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExchangeRepository::class)]
class Exchange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'exchanges_from')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Balance $balance_from = null;

    #[ORM\ManyToOne(inversedBy: 'exchanges_to')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Balance $balance_to = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column]
    private ?float $result = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBalanceFrom(): ?Balance
    {
        return $this->balance_from;
    }

    public function setBalanceFrom(?Balance $balance_from): static
    {
        $this->balance_from = $balance_from;

        return $this;
    }

    public function getBalanceTo(): ?Balance
    {
        return $this->balance_to;
    }

    public function setBalanceTo(?Balance $balance_to): static
    {
        $this->balance_to = $balance_to;

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

    public function getResult(): ?float
    {
        return $this->result;
    }

    public function setResult(float $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getRate(): float
    {
        return $this->getResult() / $this->getAmount();
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

    public function __toString(): string
    {
        return $this->getBalanceFrom() . ' to ' . $this->getBalanceTo() . ' - ' . $this->getCreatedAt()->format('d-m-Y');
    }
}
