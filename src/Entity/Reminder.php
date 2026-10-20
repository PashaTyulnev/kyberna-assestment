<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReminderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Eine Erinnerung an eine überfällige Ausleihe.
 */
#[ORM\Entity(repositoryClass: ReminderRepository::class)]
#[ORM\Table(name: 'reminders')]
class Reminder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Loan $overdueLoan = null;

    /**
     * Gibt die ID zurück.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gibt das Datum der Erinnerung zurück.
     *
     * @return \DateTimeImmutable|null
     */
    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * Setzt das Datum der Erinnerung.
     *
     * @param \DateTimeImmutable $date
     *
     * @return static
     */
    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Gibt die überfällige Ausleihe zurück, an die erinnert wird.
     *
     * @return Loan|null
     */
    public function getOverdueLoan(): ?Loan
    {
        return $this->overdueLoan;
    }

    /**
     * Setzt die überfällige Ausleihe, an die erinnert wird.
     *
     * @param Loan $overdueLoan
     *
     * @return static
     */
    public function setOverdueLoan(Loan $overdueLoan): static
    {
        $this->overdueLoan = $overdueLoan;

        return $this;
    }
}
