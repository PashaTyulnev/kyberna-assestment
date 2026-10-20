<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\OverdueLoansReminderCommand;
use App\Entity\Loan;
use App\Entity\Reminder;
use App\Repository\LoanRepository;
use App\Repository\ReminderRepository;
use App\Service\ReminderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Unit-Test für den OverdueLoansReminderCommand, ohne Datenbank (Repositories und EntityManager gemockt).
 */
class OverdueLoansReminderCommandTest extends TestCase
{
    /**
     * Der Command gleicht die Erinnerungen ab und gibt die Anzahl angelegter und entfernter Erinnerungen aus.
     *
     * @return void
     */
    public function testReportsCreatedAndRemovedReminders(): void
    {
        $loanRepository = $this->createStub(LoanRepository::class);
        $loanRepository->method('findOverdue')->willReturn([new Loan(), new Loan()]);

        $outdated = (new Reminder())->setOverdueLoan(new Loan())->setDate(new \DateTimeImmutable('2026-03-09'));
        $reminderRepository = $this->createStub(ReminderRepository::class);
        $reminderRepository->method('findAll')->willReturn([$outdated]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))->method('persist');
        $entityManager->expects(self::once())->method('remove')->with($outdated);
        $entityManager->expects(self::once())->method('flush');

        $command = new OverdueLoansReminderCommand(
            new ReminderService($loanRepository, $reminderRepository, $entityManager, new MockClock('2026-03-10 08:00:00')),
        );

        $output = new BufferedOutput();
        $exitCode = $command(new SymfonyStyle(new ArrayInput([]), $output));

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('2 Erinnerung(en) erstellt, 1 veraltete entfernt.', $output->fetch());
    }
}
