<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\Loan;
use App\Message\SyncOverdueReminders;
use App\MessageHandler\SyncOverdueRemindersHandler;
use App\Repository\LoanRepository;
use App\Repository\ReminderRepository;
use App\Service\ReminderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * Unit-Test für den SyncOverdueRemindersHandler, ohne Datenbank (Repositories und EntityManager gemockt).
 */
class SyncOverdueRemindersHandlerTest extends TestCase
{
    /**
     * Der Handler gleicht über den ReminderService ab und gibt dessen Ergebnis zurück.
     *
     * @return void
     */
    public function testSyncsRemindersViaService(): void
    {
        $loanRepository = $this->createStub(LoanRepository::class);
        $loanRepository->method('findOverdue')->willReturn([new Loan()]);

        $reminderRepository = $this->createStub(ReminderRepository::class);
        $reminderRepository->method('findAll')->willReturn([]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $handler = new SyncOverdueRemindersHandler(
            new ReminderService($loanRepository, $reminderRepository, $entityManager, new MockClock('2026-03-10 08:00:00')),
        );

        self::assertSame(['created' => 1, 'removed' => 0], $handler(new SyncOverdueReminders()));
    }
}
