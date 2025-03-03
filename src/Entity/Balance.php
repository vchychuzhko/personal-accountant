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
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Deposit::class, mappedBy: 'balance')]
    private Collection $deposits;

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
        $this->deposits = new ArrayCollection();
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

    public function getAmountInUsdAtMoment(\DateTime $dateTime): ?float
    {
        $amountInUsd = $this->getAmountInUsd();

        $incomes = $this->getIncomes()->filter(function(Income $income) use ($dateTime) {
            return $income->getCreatedAt() >= $dateTime;
        });
        foreach ($incomes as $income) {
            $amountInUsd = $amountInUsd - $income->getAmountInUsd();
        }

        $payments = $this->getPayments()->filter(function(Payment $payment) use ($dateTime) {
            return $payment->getCreatedAt() >= $dateTime;
        });
        foreach ($payments as $payment) {
            $amountInUsd = $amountInUsd + $payment->getAmountInUsd();
        }

        $exchangesFrom = $this->getExchangesFrom()->filter(function(Exchange $exchange) use ($dateTime) {
            return $exchange->getCreatedAt() >= $dateTime;
        });
        foreach ($exchangesFrom as $exchange) {
            $amountInUsd = $amountInUsd + $exchange->getAmountInUsd();
        }
        $exchangesTo = $this->getExchangesTo()->filter(function(Exchange $exchange) use ($dateTime) {
            return $exchange->getCreatedAt() >= $dateTime;
        });
        foreach ($exchangesTo as $exchange) {
            $amountInUsd = $amountInUsd - $exchange->getResultInUsd();
        }

        return $amountInUsd;
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
     * @return Collection<int, Deposit>
     */
    public function getDeposits(): Collection
    {
        return $this->deposits;
    }

    /**
     * @return Collection<int, Deposit>
     */
    public function getActiveDeposits(): Collection
    {
        return $this->deposits->filter(fn(Deposit $deposit) => $deposit->isActive());
    }

    public function addDeposit(Deposit $deposit): static
    {
        if (!$this->deposits->contains($deposit)) {
            $this->deposits->add($deposit);
            $deposit->setBalance($this);
        }

        return $this;
    }

    public function removeDeposit(Deposit $deposit): static
    {
        if ($this->deposits->removeElement($deposit)) {
            // set the owning side to null (unless already changed)
            if ($deposit->getBalance() === $this) {
                $deposit->setBalance(null);
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
        $currency = $this->getCurrency();

        return $this->getName() . ' (' . $currency . ')';
    }
}
