<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Erstellt Erinnerungen für überfällige Ausleihen und entfernt veraltete. Gedacht für den täglichen Cronjob.
 */
#[AsCommand(name: 'app:overdue-loans-reminder', description: 'Erstellt Erinnerungen für überfällige Ausleihen und entfernt veraltete')]
class OverdueLoansReminderCommand
{
    /**
     * @param ReminderService $reminderService gleicht die Erinnerungen ab
     */
    public function __construct(
        private ReminderService $reminderService,
    ) {
    }

    /**
     * Gleicht die Tabelle reminders mit den aktuell überfälligen Ausleihen ab.
     *
     * @param SymfonyStyle $io Konsolenausgabe
     *
     * @return int Exit-Code des Commands
     */
    public function __invoke(SymfonyStyle $io): int
    {
        $result = $this->reminderService->syncOverdueReminders();

        $io->success(sprintf(
            '%d Erinnerung(en) erstellt, %d veraltete entfernt.',
            $result['created'],
            $result['removed'],
        ));

        return Command::SUCCESS;
    }
}
