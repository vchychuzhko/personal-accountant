<?php

namespace App\Entity;

use App\Repository\BalanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BalanceRepository::class)]
class Balance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'balances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Currency $currency = null;

    #[ORM\Column]
    private ?float $amount = null;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'balance')]
    private Collection $transactions;

    /**
     * @var Collection<int, Exchange>
     */
    #[ORM\OneToMany(targetEntity: Exchange::class, mappedBy: 'balance_from')]
    private Collection $exchanges_from;

    /**
     * @var Collection<int, Exchange>
     */
    #[ORM\OneToMany(targetEntity: Exchange::class, mappedBy: 'balance_to')]
    private Collection $exchanges_to;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->exchanges_from = new ArrayCollection();
        $this->exchanges_to = new ArrayCollection();
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

    public function getAmountInUsd(): ?float
    {
        $currency = $this->getCurrency();

        return $this->getAmount() / $currency->getRate();
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setBalance($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getBalance() === $this) {
                $transaction->setBalance(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Exchange>
     */
    public function getExchangesFrom(): Collection
    {
        return $this->exchanges_from;
    }

    public function addExchangeFrom(Exchange $exchange): static
    {
        if (!$this->exchanges_from->contains($exchange)) {
            $this->exchanges_from->add($exchange);
            $exchange->setBalanceFrom($this);
        }

        return $this;
    }

    public function removeExchangeFrom(Exchange $exchange): static
    {
        if ($this->exchanges_from->removeElement($exchange)) {
            // set the owning side to null (unless already changed)
            if ($exchange->getBalanceFrom() === $this) {
                $exchange->setBalanceFrom(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Exchange>
     */
    public function getExchangesTo(): Collection
    {
        return $this->exchanges_to;
    }

    public function addExchangeTo(Exchange $exchange): static
    {
        if (!$this->exchanges_to->contains($exchange)) {
            $this->exchanges_to->add($exchange);
            $exchange->setBalanceFrom($this);
        }

        return $this;
    }

    public function removeExchangeTo(Exchange $exchange): static
    {
        if ($this->exchanges_to->removeElement($exchange)) {
            // set the owning side to null (unless already changed)
            if ($exchange->getBalanceTo() === $this) {
                $exchange->setBalanceTo(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName() . ' (' . $this->getCurrency() . ')';
    }
}
