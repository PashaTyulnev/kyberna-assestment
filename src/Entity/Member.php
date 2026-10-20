<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\MemberRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ein Mitglied der Bibliothek, das Bücher ausleihen kann.
 */
#[ORM\Entity(repositoryClass: MemberRepository::class)]
#[ApiResource(operations: [])]
class Member
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: Types::DATETIME_UTC_IMMUTABLE)]
    private ?\DateTimeImmutable $membershipDate = null;

    /**
     * @var Collection<int, Loan>
     */
    #[ORM\OneToMany(targetEntity: Loan::class, mappedBy: 'member')]
    private Collection $loans;

    /**
     * Initialisiert die Collection der Ausleihen.
     */
    public function __construct()
    {
        $this->loans = new ArrayCollection();
    }

    /**
     * Gibt die ID zurück.
     *
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gibt den Namen zurück.
     *
     * @return ?string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Setzt den Namen.
     *
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gibt die E-Mail-Adresse zurück.
     *
     * @return ?string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Setzt die E-Mail-Adresse.
     *
     * @param string $email
     *
     * @return static
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Gibt das Beitrittsdatum zurück.
     *
     * @return ?\DateTimeImmutable
     */
    public function getMembershipDate(): ?\DateTimeImmutable
    {
        return $this->membershipDate;
    }

    /**
     * Setzt das Beitrittsdatum.
     *
     * @param \DateTimeImmutable $membershipDate
     *
     * @return static
     */
    public function setMembershipDate(\DateTimeImmutable $membershipDate): static
    {
        $this->membershipDate = $membershipDate;

        return $this;
    }

    /**
     * Gibt die Ausleihen zurück.
     *
     * @return Collection<int, Loan>
     */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    /**
     * Fügt eine Ausleihe hinzu und setzt die Gegenseite der Beziehung.
     *
     * @param Loan $loan
     *
     * @return static
     */
    public function addLoan(Loan $loan): static
    {
        if (!$this->loans->contains($loan)) {
            $this->loans->add($loan);
            $loan->setMember($this);
        }

        return $this;
    }

    /**
     * Entfernt eine Ausleihe und löst die Gegenseite der Beziehung.
     *
     * @param Loan $loan
     *
     * @return static
     */
    public function removeLoan(Loan $loan): static
    {
        if ($this->loans->removeElement($loan)) {
            // set the owning side to null (unless already changed)
            if ($loan->getMember() === $this) {
                $loan->setMember(null);
            }
        }

        return $this;
    }
}
