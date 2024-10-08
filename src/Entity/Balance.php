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
     * @var Collection<int, Income>
     */
    #[ORM\OneToMany(targetEntity: Income::class, mappedBy: 'balance')]
    private Collection $incomes;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'balance')]
    private Collection $payments;

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
        $this->incomes = new ArrayCollection();
        $this->payments = new ArrayCollection();
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
     * @return Collection<int, Income>
     */
    public function getIncomes(): Collection
    {
        return $this->incomes;
    }

    public function addIncome(Income $income): static
    {
        if (!$this->incomes->contains($income)) {
            $this->incomes->add($income);
            $income->setBalance($this);
        }

        return $this;
    }

    public function removeIncome(Income $income): static
    {
        if ($this->incomes->removeElement($income)) {
            // set the owning side to null (unless already changed)
            if ($income->getBalance() === $this) {
                $income->setBalance(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setBalance($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getBalance() === $this) {
                $payment->setBalance(null);
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
