<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Loan;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class LoanService
{
    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    /**
     * Bereitet eine neue Ausleihe vor: prüft die Verfügbarkeit, reduziert die Kopien und setzt die Standarddaten.
     *
     * @throws ConflictHttpException
     */
    public function prepareLoan(Loan $loan): void
    {
        $this->checkEnoughCopies($loan->getBook());
        $this->decreaseCopies($loan->getBook());
        $this->setDefaultDates($loan);
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
}
