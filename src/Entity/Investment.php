<?php

namespace App\Entity;

use App\Repository\InvestmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvestmentRepository::class)]
class Investment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $share = null;

    #[ORM\Column]
    private ?float $price = null;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'investment')]
    #[ORM\OrderBy(['created_at' => 'DESC'])]
    private Collection $payments;

    /**
     * @var Collection<int, Income>
     */
    #[ORM\OneToMany(targetEntity: Income::class, mappedBy: 'investment')]
    #[ORM\OrderBy(['created_at' => 'DESC'])]
    private Collection $incomes;

    #[ORM\ManyToOne(inversedBy: 'investments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Currency $currency = null;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
        $this->incomes = new ArrayCollection();
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

    public function getShare(): ?float
    {
        return $this->share;
    }

    public function isActive(): bool
    {
        return $this->getShare() > 0;
    }

    public function setShare(float $share): static
    {
        $this->share = $share;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getValue(): ?float
    {
        return $this->isActive() ? $this->getShare() * $this->getPrice() : $this->getSoldValue();
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function getPurchasedValue(): ?float
    {
        $payments = $this->getPayments();

        return $payments->reduce(fn(float $value, Payment $payment) => $value + $payment->getAmount(), 0);
    }

    public function getDifference(): ?float
    {
        return $this->getValue() - $this->getPurchasedValue();
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setInvestment($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getInvestment() === $this) {
                $payment->setInvestment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Income>
     */
    public function getIncomes(): Collection
    {
        return $this->incomes;
    }

    public function getSoldValue(): ?float
    {
        $incomes = $this->getIncomes();

        return $incomes->reduce(fn(float $value, Income $income) => $value + $income->getAmount(), 0);
    }

    public function addIncome(Income $income): static
    {
        if (!$this->incomes->contains($income)) {
            $this->incomes->add($income);
            $income->setInvestment($this);
        }

        return $this;
    }

    public function removeIncome(Income $income): static
    {
        if ($this->incomes->removeElement($income)) {
            // set the owning side to null (unless already changed)
            if ($income->getInvestment() === $this) {
                $income->setInvestment(null);
            }
        }

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

    public function __toString(): string
    {
        return $this->getName() . ' (' . $this->getShare() . ')';
    }
}
