<?php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CurrencyRepository::class)]
class Currency
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column]
    private ?float $rate = null;

    #[ORM\Column(length: 255)]
    private ?string $format = null;

    /**
     * @var Collection<int, Balance>
     */
    #[ORM\OneToMany(targetEntity: Balance::class, mappedBy: 'currency')]
    private Collection $balances;

    /**
     * @var Collection<int, Loan>
     */
    #[ORM\OneToMany(targetEntity: Loan::class, mappedBy: 'currency')]
    private Collection $loans;

    public function __construct()
    {
        $this->balances = new ArrayCollection();
        $this->loans = new ArrayCollection();
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): static
    {
        $this->rate = $rate;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return Collection<int, Balance>
     */
    public function getBalances(): Collection
    {
        return $this->balances;
    }

    public function addBalance(Balance $balance): static
    {
        if (!$this->balances->contains($balance)) {
            $this->balances->add($balance);
            $balance->setCurrency($this);
        }

        return $this;
    }

    public function removeBalance(Balance $balance): static
    {
        if ($this->balances->removeElement($balance)) {
            // set the owning side to null (unless already changed)
            if ($balance->getCurrency() === $this) {
                $balance->setCurrency(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Deposit>
     */
    public function getDeposits(): Collection
    {
        $deposits = new ArrayCollection();

        foreach ($this->getBalances() as $balance) {
            foreach ($balance->getDeposits() as $deposit) {
                $deposits->add($deposit);
            }
        }

        return $deposits;
    }

    /**
     * @return Collection<int, Loan>
     */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function addLoan(Loan $loan): static
    {
        if (!$this->loans->contains($loan)) {
            $this->loans->add($loan);
            $loan->setCurrency($this);
        }

        return $this;
    }

    public function removeLoan(Loan $loan): static
    {
        if ($this->loans->removeElement($loan)) {
            // set the owning side to null (unless already changed)
            if ($loan->getCurrency() === $this) {
                $loan->setCurrency(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getCode();
    }
}
