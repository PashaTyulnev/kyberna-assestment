<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Book;
use App\Entity\Loan;
use App\Service\LoanService;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Unit-Tests für den LoanService, ohne Datenbank und mit fester Uhr.
 */
class LoanServiceTest extends TestCase
{
    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    /**
     * Eine neue Ausleihe reduziert die verfügbaren Kopien um 1.
     *
     * @return void
     */
    public function testPrepareLoanDecreasesAvailableCopies(): void
    {
        $loan = $this->createLoan(availableCopies: 2);

        $this->createService()->prepareLoan($loan);

        self::assertSame(1, $loan->getBook()->getAvailableCopies());
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    /**
     * Eine neue Ausleihe bekommt loanedAt = jetzt, dueAt = +2 Wochen und returnedAt = null.
     *
     * @return void
     */
    public function testPrepareLoanSetsDefaultDates(): void
    {
        // vom Client gesendete Werte müssen überschrieben werden
        $loan = $this->createLoan(availableCopies: 1)
            ->setLoanedAt(new \DateTimeImmutable('2000-01-01'))
            ->setDueAt(new \DateTimeImmutable('2000-01-02'))
            ->setReturnedAt(new \DateTimeImmutable('2000-01-03'));

        $this->createService('2026-01-15 10:00:00')->prepareLoan($loan);

        self::assertSame('2026-01-15 10:00:00', $loan->getLoanedAt()->format('Y-m-d H:i:s'));
        self::assertSame('2026-01-29 10:00:00', $loan->getDueAt()->format('Y-m-d H:i:s'));
        self::assertNull($loan->getReturnedAt());
    }

    /**
     * Ohne verfügbare Kopien wird eine 409-Exception geworfen und nichts verändert.
     *
     * @return void
     */
    public function testPrepareLoanThrowsConflictWhenNoCopiesAvailable(): void
    {
        $loan = $this->createLoan(availableCopies: 0);

        $clock = $this->createMock(ClockInterface::class);
        $clock->expects(self::never())->method('now');

        try {
            new LoanService($clock)->prepareLoan($loan);
            self::fail('ConflictHttpException expected');
        } catch (ConflictHttpException $e) {
            self::assertSame(409, $e->getStatusCode());
        }

        self::assertSame(0, $loan->getBook()->getAvailableCopies());
        self::assertNull($loan->getLoanedAt());
    }

    /**
     * @throws \DateInvalidTimeZoneException
     * @throws \DateMalformedStringException
     */
    /**
     * Die Rückgabe setzt returnedAt und erhöht die verfügbaren Kopien um 1.
     *
     * @return void
     */
    public function testReturnLoanSetsReturnedAtAndIncreasesAvailableCopies(): void
    {
        $loan = $this->createLoan(availableCopies: 0);

        $this->createService('2026-02-01 12:00:00')->returnLoan($loan);

        self::assertSame('2026-02-01 12:00:00', $loan->getReturnedAt()?->format('Y-m-d H:i:s'));
        self::assertSame(1, $loan->getBook()->getAvailableCopies());
    }

    /**
     * Eine bereits zurückgegebene Ausleihe kann nicht erneut zurückgegeben werden.
     *
     * @return void
     */
    public function testReturnLoanThrowsConflictWhenAlreadyReturned(): void
    {
        $returnedAt = new \DateTimeImmutable('2026-02-01 12:00:00');
        $loan = $this->createLoan(availableCopies: 1)->setReturnedAt($returnedAt);

        $this->expectException(ConflictHttpException::class);

        try {
            $this->createService()->returnLoan($loan);
        } finally {
            self::assertSame(1, $loan->getBook()->getAvailableCopies());
            self::assertSame($returnedAt, $loan->getReturnedAt());
        }
    }

    /**
     * @throws \DateInvalidTimeZoneException
     * @throws \DateMalformedStringException
     */
    private function createService(string $now = 'now'): LoanService
    {
        return new LoanService(new MockClock($now));
    }

    private function createLoan(int $availableCopies): Loan
    {
        return new Loan()->setBook(new Book()->setAvailableCopies($availableCopies));
    }
}
