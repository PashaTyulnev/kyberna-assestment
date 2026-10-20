<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\OverdueLoansReminderCommand;
use App\Message\SyncOverdueReminders;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Unit-Test für den OverdueLoansReminderCommand, ohne Datenbank (MessageBus gemockt).
 */
class OverdueLoansReminderCommandTest extends TestCase
{
    /**
     * Der Command verschickt SyncOverdueReminders und gibt das Ergebnis des Handlers aus.
     *
     * @return void
     */
    public function testDispatchesMessageAndReportsResult(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(SyncOverdueReminders::class))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope(
                $message,
                [new HandledStamp(['created' => 2, 'removed' => 1], 'handler')],
            ));

        $output = new BufferedOutput();
        $exitCode = (new OverdueLoansReminderCommand($messageBus))(new SymfonyStyle(new ArrayInput([]), $output));

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('2 Erinnerung(en) erstellt, 1 veraltete entfernt.', $output->fetch());
    }

    /**
     * Wird die Message asynchron verarbeitet (kein HandledStamp), meldet der Command nur das Anstoßen.
     *
     * @return void
     */
    public function testReportsDispatchWhenHandledAsynchronously(): void
    {
        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        $output = new BufferedOutput();
        $exitCode = (new OverdueLoansReminderCommand($messageBus))(new SymfonyStyle(new ArrayInput([]), $output));

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Abgleich der Erinnerungen angestoßen.', $output->fetch());
    }
}
