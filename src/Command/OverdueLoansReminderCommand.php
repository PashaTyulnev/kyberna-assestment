<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\SyncOverdueReminders;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Stößt den Abgleich der Erinnerungen für überfällige Ausleihen über Messenger an. Gedacht für den täglichen Cronjob.
 */
#[AsCommand(name: 'app:overdue-loans-reminder', description: 'Erstellt Erinnerungen für überfällige Ausleihen und entfernt veraltete')]
class OverdueLoansReminderCommand
{
    /**
     * @param MessageBusInterface $messageBus verschickt die Message SyncOverdueReminders
     */
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * Verschickt SyncOverdueReminders und gibt das Ergebnis des Handlers aus.
     *
     * @param SymfonyStyle $io Konsolenausgabe
     *
     * @return int Exit-Code des Commands
     */
    public function __invoke(SymfonyStyle $io): int
    {
        $envelope = $this->messageBus->dispatch(new SyncOverdueReminders());

        /** @var array{created: int, removed: int}|null $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        if (null === $result) {
            // Message wurde an einen asynchronen Transport übergeben
            $io->success('Abgleich der Erinnerungen angestoßen.');

            return Command::SUCCESS;
        }

        $io->success(sprintf(
            '%d Erinnerung(en) erstellt, %d veraltete entfernt.',
            $result['created'],
            $result['removed'],
        ));

        return Command::SUCCESS;
    }
}
