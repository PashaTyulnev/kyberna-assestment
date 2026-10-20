<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Loan;
use App\Entity\Reminder;
use App\Repository\LoanRepository;
use App\Repository\ReminderRepository;
use App\Service\ReminderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * Unit-Tests für den ReminderService, ohne Datenbank (Repositories und EntityManager gemockt).
 */
class ReminderServiceTest extends TestCase
{
    private const string NOW = '2026-03-10 08:00:00';

    private EntityManagerInterface&MockObject $entityManager;

    /** @var list<object> */
    private array $persisted = [];

    /** @var list<object> */
    private array $removed = [];

    /**
     * Bereitet einen EntityManager-Mock vor, der persist/remove mitschreibt.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->persisted = [];
        $this->removed = [];

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });
        $this->entityManager->method('remove')->willReturnCallback(function (object $entity): void {
            $this->removed[] = $entity;
        });
        $this->entityManager->expects(self::once())->method('flush');
    }

    /**
     * Für eine überfällige Ausleihe ohne Erinnerung wird eine mit dem heutigen Datum angelegt.
     *
     * @return void
     */
    public function testCreatesReminderForOverdueLoan(): void
    {
        $loan = new Loan();

        $result = $this->createService(overdueLoans: [$loan], reminders: [])->syncOverdueReminders();

        self::assertSame(['created' => 1, 'removed' => 0], $result);
        self::assertCount(1, $this->persisted);
        self::assertInstanceOf(Reminder::class, $this->persisted[0]);
        self::assertSame($loan, $this->persisted[0]->getOverdueLoan());
        self::assertSame('2026-03-10', $this->persisted[0]->getDate()?->format('Y-m-d'));
        self::assertSame([], $this->removed);
    }

    /**
     * Eine heutige Erinnerung zu einer weiterhin überfälligen Ausleihe bleibt bestehen und wird nicht doppelt angelegt.
     *
     * @return void
     */
    public function testKeepsCurrentReminder(): void
    {
        $loan = new Loan();
        $reminder = $this->reminder($loan, '2026-03-10');

        $result = $this->createService(overdueLoans: [$loan], reminders: [$reminder])->syncOverdueReminders();

        self::assertSame(['created' => 0, 'removed' => 0], $result);
        self::assertSame([], $this->persisted);
        self::assertSame([], $this->removed);
    }

    /**
     * Erinnerungen zu Ausleihen, die nicht mehr überfällig sind (z. B. zurückgegeben), werden entfernt.
     *
     * @return void
     */
    public function testRemovesReminderForLoanNoLongerOverdue(): void
    {
        $reminder = $this->reminder(new Loan(), '2026-03-10');

        $result = $this->createService(overdueLoans: [], reminders: [$reminder])->syncOverdueReminders();

        self::assertSame(['created' => 0, 'removed' => 1], $result);
        self::assertSame([$reminder], $this->removed);
        self::assertSame([], $this->persisted);
    }

    /**
     * Eine Erinnerung von gestern wird entfernt und durch eine heutige ersetzt.
     *
     * @return void
     */
    public function testReplacesReminderFromPreviousDay(): void
    {
        $loan = new Loan();
        $oldReminder = $this->reminder($loan, '2026-03-09');

        $result = $this->createService(overdueLoans: [$loan], reminders: [$oldReminder])->syncOverdueReminders();

        self::assertSame(['created' => 1, 'removed' => 1], $result);
        self::assertSame([$oldReminder], $this->removed);
        self::assertCount(1, $this->persisted);
        self::assertSame($loan, $this->persisted[0]->getOverdueLoan());
        self::assertSame('2026-03-10', $this->persisted[0]->getDate()?->format('Y-m-d'));
    }

    /**
     * Der Stichtag für die Abfrage überfälliger Ausleihen ist der aktuelle Zeitpunkt der Uhr.
     *
     * @return void
     */
    public function testQueriesOverdueLoansWithCurrentTime(): void
    {
        $loanRepository = $this->createMock(LoanRepository::class);
        $loanRepository->expects(self::once())
            ->method('findOverdue')
            ->with(self::callback(static fn (\DateTimeImmutable $now): bool => self::NOW === $now->format('Y-m-d H:i:s')))
            ->willReturn([]);

        $reminderRepository = $this->createStub(ReminderRepository::class);
        $reminderRepository->method('findAll')->willReturn([]);

        (new ReminderService($loanRepository, $reminderRepository, $this->entityManager, new MockClock(self::NOW)))
            ->syncOverdueReminders();
    }

    /**
     * Erzeugt den Service mit gestubbten Repositories.
     *
     * @param list<Loan>     $overdueLoans Rückgabe von LoanRepository::findOverdue()
     * @param list<Reminder> $reminders    Rückgabe von ReminderRepository::findAll()
     *
     * @return ReminderService
     */
    private function createService(array $overdueLoans, array $reminders): ReminderService
    {
        $loanRepository = $this->createStub(LoanRepository::class);
        $loanRepository->method('findOverdue')->willReturn($overdueLoans);

        $reminderRepository = $this->createStub(ReminderRepository::class);
        $reminderRepository->method('findAll')->willReturn($reminders);

        return new ReminderService($loanRepository, $reminderRepository, $this->entityManager, new MockClock(self::NOW));
    }

    /**
     * Erzeugt eine Erinnerung zu einer Ausleihe an einem Datum.
     *
     * @param Loan   $loan
     * @param string $date Datum im Format Y-m-d
     *
     * @return Reminder
     */
    private function reminder(Loan $loan, string $date): Reminder
    {
        return (new Reminder())->setOverdueLoan($loan)->setDate(new \DateTimeImmutable($date));
    }
}
