<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Book;
use App\Entity\Loan;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Geschäftslogik für Ausleihe und Rückgabe von Büchern.
 */
final class LoanService
{
    /**
     * @param ClockInterface $clock liefert den aktuellen Zeitpunkt (im Test austauschbar)
     */
    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    /**
     * Bereitet eine neue Ausleihe vor: prüft die Verfügbarkeit, reduziert die Kopien und setzt die Standarddaten.
     *
     * @param Loan $loan die neue Ausleihe
     *
     * @return void
     *
     * @throws ConflictHttpException
     */
    public function prepareLoan(Loan $loan): void
    {
        $this->checkEnoughCopies($loan->getBook());
        $this->decreaseCopies($loan->getBook());
        $this->setDefaultDates($loan);
    }

    /**
     * Markiert eine Ausleihe als zurückgegeben und gibt die Kopie wieder frei.
     *
     * @param Loan $loan die zurückgegebene Ausleihe
     *
     * @return void
     *
     * @throws ConflictHttpException
     */
    public function returnLoan(Loan $loan): void
    {
        if (null !== $loan->getReturnedAt()) {
            throw new ConflictHttpException('Loan already returned');
        }

        $loan->setReturnedAt($this->clock->now());
        $this->increaseCopies($loan->getBook());
    }

    private function setDefaultDates(Loan $loan): void
    {
        $loanedAt = $this->clock->now();

        $loan
            ->setLoanedAt($loanedAt)
            ->setDueAt($loanedAt->add(new \DateInterval(Loan::LOAN_PERIOD)))
            ->setReturnedAt(null);
    }

    /**
     * @throws ConflictHttpException
     */
    private function checkEnoughCopies(Book $book): void
    {
        if ($book->getAvailableCopies() <= 0) {
            throw new ConflictHttpException('No copies available');
        }
    }

    private function decreaseCopies(Book $book): void
    {
        $book->setAvailableCopies($book->getAvailableCopies() - 1);
    }

    private function increaseCopies(Book $book): void
    {
        $book->setAvailableCopies($book->getAvailableCopies() + 1);
    }
}
