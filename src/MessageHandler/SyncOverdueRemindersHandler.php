<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SyncOverdueReminders;
use App\Service\ReminderService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Verarbeitet SyncOverdueReminders über den ReminderService.
 */
#[AsMessageHandler]
final class SyncOverdueRemindersHandler
{
    /**
     * @param ReminderService $reminderService gleicht die Erinnerungen ab
     */
    public function __construct(
        private readonly ReminderService $reminderService,
    ) {
    }

    /**
     * Gleicht die Erinnerungen ab.
     *
     * @param SyncOverdueReminders $message
     *
     * @return array{created: int, removed: int} Anzahl angelegter und entfernter Erinnerungen
     */
    public function __invoke(SyncOverdueReminders $message): array
    {
        return $this->reminderService->syncOverdueReminders();
    }
}
