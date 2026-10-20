<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Reminder;
use App\Repository\LoanRepository;
use App\Repository\ReminderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Gleicht die Erinnerungen mit den aktuell überfälligen Ausleihen ab.
 */
final class ReminderService
{
    /**
     * @param LoanRepository         $loanRepository     liefert die überfälligen Ausleihen
     * @param ReminderRepository     $reminderRepository liefert die vorhandenen Erinnerungen
     * @param EntityManagerInterface $entityManager      speichert und löscht Erinnerungen
     * @param ClockInterface         $clock              liefert den aktuellen Zeitpunkt (im Test austauschbar)
     */
    public function __construct(
        private LoanRepository $loanRepository,
        private ReminderRepository $reminderRepository,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Legt für jede überfällige Ausleihe eine Erinnerung mit dem heutigen Datum an
     * und entfernt alle Erinnerungen, die nicht mehr aktuell sind
     * (Ausleihe nicht mehr überfällig oder Erinnerung von einem früheren Tag).
     *
     * @return array{created: int, removed: int} Anzahl angelegter und entfernter Erinnerungen
     */
    public function syncOverdueReminders(): array
    {
        $now = $this->clock->now();
        $today = $now->setTime(0, 0);

        $overdueLoans = [];
        foreach ($this->loanRepository->findOverdue($now) as $loan) {
            $overdueLoans[spl_object_id($loan)] = $loan;
        }

        $removed = 0;
        foreach ($this->reminderRepository->findAll() as $reminder) {
            $loanKey = spl_object_id($reminder->getOverdueLoan());
            $isCurrent = isset($overdueLoans[$loanKey])
                && $reminder->getDate()?->format('Y-m-d') === $today->format('Y-m-d');

            if ($isCurrent) {
                // Ausleihe hat schon eine aktuelle Erinnerung
                unset($overdueLoans[$loanKey]);
                continue;
            }

            $this->entityManager->remove($reminder);
            ++$removed;
        }

        foreach ($overdueLoans as $loan) {
            $this->entityManager->persist(
                new Reminder()
                    ->setDate($today)
                    ->setOverdueLoan($loan)
            );
        }

        $this->entityManager->flush();

        return ['created' => \count($overdueLoans), 'removed' => $removed];
    }
}
