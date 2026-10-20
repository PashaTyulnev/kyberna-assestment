<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\Member;
use App\Service\ReminderService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Random\RandomException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Erstellt zusammenhängende Dummy-Daten für Entwicklung und Tests: Bücher, Mitglieder, Ausleihen
 * und die Erinnerungen für die überfälligen Ausleihen.
 *
 * Die Ausleihen liegen in den letzten MAX_LOAN_DAYS_AGO Tagen, sodass ein Teil überfällig ist.
 * Ein Teil ist bereits zurückgegeben; nur offene Ausleihen reduzieren die verfügbaren Kopien.
 */
#[AsCommand(name: 'app:create-dummy-data', description: 'Erstellt zusammenhängende Dummy-Daten (Bücher, Mitglieder, Ausleihen, Erinnerungen)')]
class CreateDummyDataCommand
{
    private const int MAX_LOAN_DAYS_AGO = 30;
    private const int RETURNED_PERCENT = 30;

    private const array ADJECTIVES = ['Verlorene', 'Dunkle', 'Stille', 'Letzte', 'Goldene', 'Vergessene', 'Ewige', 'Geheime'];
    private const array NOUNS = ['Stadt', 'Reise', 'Insel', 'Nacht', 'Erinnerung', 'Grenze', 'Wahrheit', 'Spur'];
    private const array FIRST_NAMES = ['Anna', 'Lukas', 'Marie', 'Felix', 'Sophie', 'Jonas', 'Lena', 'Paul'];
    private const array LAST_NAMES = ['Müller', 'Schmidt', 'Weber', 'Fischer', 'Wagner', 'Becker', 'Hoffmann', 'Koch'];

    /**
     * @param EntityManagerInterface $entityManager   zum Speichern der Daten
     * @param ReminderService        $reminderService legt die Erinnerungen für überfällige Ausleihen an
     * @param ClockInterface         $clock           liefert den aktuellen Zeitpunkt
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReminderService $reminderService,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Legt Bücher, Mitglieder und Ausleihen an und erzeugt danach die Erinnerungen.
     *
     * @param SymfonyStyle $io      Konsolenausgabe
     * @param int          $books   Anzahl der zu erstellenden Bücher
     * @param int          $members Anzahl der zu erstellenden Mitglieder
     * @param int          $loans   Anzahl der zu erstellenden Ausleihen
     *
     * @return int Exit-Code des Commands
     *
     * @throws RandomException
     * @throws \DateMalformedStringException
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Anzahl der Bücher')] int $books = 50,
        #[Option(description: 'Anzahl der Mitglieder')] int $members = 50,
        #[Option(description: 'Anzahl der Ausleihen')] int $loans = 30,
    ): int {
        if ($books < 1 || $members < 1 || $loans < 0) {
            $io->error('Es wird mindestens 1 Buch und 1 Mitglied benötigt, die Anzahl der Ausleihen darf nicht negativ sein.');

            return Command::INVALID;
        }

        $now = $this->clock->now();

        $createdBooks = $this->createBooks($books);
        $createdMembers = $this->createMembers($members, $now);
        [$createdLoans, $openOverdue] = $this->createLoans($loans, $createdBooks, $createdMembers, $now);

        $this->entityManager->flush();

        $reminders = $this->reminderService->syncOverdueReminders();

        $this->entityManager->clear();

        $io->success(sprintf(
            '%d Bücher, %d Mitglieder, %d Ausleihen (davon %d offen überfällig) und %d Erinnerungen erstellt.',
            \count($createdBooks),
            \count($createdMembers),
            $createdLoans,
            $openOverdue,
            $reminders['created'],
        ));

        if ($createdLoans < $loans) {
            $io->warning(sprintf('Nur %d von %d Ausleihen erstellt, weil keine Kopien mehr verfügbar waren.', $createdLoans, $loans));
        }

        return Command::SUCCESS;
    }

    /**
     * @param int $count Anzahl der Bücher
     *
     * @return list<Book>
     *
     * @throws RandomException
     */
    private function createBooks(int $count): array
    {
        $books = [];

        for ($i = 0; $i < $count; ++$i) {
            $book = (new Book())
                ->setTitle(sprintf('Die %s %s', self::pick(self::ADJECTIVES), self::pick(self::NOUNS)))
                ->setAuthor(sprintf('%s %s', self::pick(self::FIRST_NAMES), self::pick(self::LAST_NAMES)))
                ->setIsbn(self::randomIsbn13())
                ->setAvailableCopies(random_int(0, 10));

            $this->entityManager->persist($book);
            $books[] = $book;
        }

        return $books;
    }

    /**
     * @param int                $count Anzahl der Mitglieder
     * @param \DateTimeImmutable $now   aktueller Zeitpunkt
     *
     * @return list<Member>
     *
     * @throws RandomException
     */
    private function createMembers(int $count, \DateTimeImmutable $now): array
    {
        $members = [];
        $utc = new \DateTimeZone('UTC');

        for ($i = 0; $i < $count; ++$i) {
            $firstName = self::pick(self::FIRST_NAMES);
            $lastName = self::pick(self::LAST_NAMES);

            $member = (new Member())
                ->setName(sprintf('%s %s', $firstName, $lastName))
                ->setEmail(self::email($firstName, $lastName))
                ->setMembershipDate($now->setTimezone($utc)->modify(sprintf('-%d days', random_int(0, 3 * 365))));

            $this->entityManager->persist($member);
            $members[] = $member;
        }

        return $members;
    }

    /**
     * Legt Ausleihen an; offene Ausleihen nur für Bücher mit verfügbaren Kopien.
     *
     * @param int                $count   Anzahl der Ausleihen
     * @param list<Book>         $books   Bücher, aus denen gewählt wird
     * @param list<Member>       $members Mitglieder, aus denen gewählt wird
     * @param \DateTimeImmutable $now     aktueller Zeitpunkt
     *
     * @return array{int, int} Anzahl erstellter Ausleihen und davon offen überfälliger
     *
     * @throws RandomException
     */
    private function createLoans(int $count, array $books, array $members, \DateTimeImmutable $now): array
    {
        $created = 0;
        $overdue = 0;

        for ($i = 0; $i < $count; ++$i) {
            $returned = random_int(1, 100) <= self::RETURNED_PERCENT;
            $candidates = $returned
                ? $books
                : array_values(array_filter($books, static fn (Book $book): bool => $book->getAvailableCopies() > 0));

            if ([] === $candidates) {
                break;
            }

            $book = $candidates[array_rand($candidates)];
            $loanedAt = $now->modify(sprintf('-%d days -%d minutes', random_int(0, self::MAX_LOAN_DAYS_AGO), random_int(0, 1439)));
            $dueAt = $loanedAt->add(new \DateInterval(Loan::LOAN_PERIOD));

            $loan = (new Loan())
                ->setBook($book)
                ->setMember($members[array_rand($members)])
                ->setLoanedAt($loanedAt)
                ->setDueAt($dueAt);

            if ($returned) {
                $loan->setReturnedAt($loanedAt->setTimestamp(random_int($loanedAt->getTimestamp(), min($dueAt, $now)->getTimestamp())));
            } else {
                $book->setAvailableCopies($book->getAvailableCopies() - 1);
                $overdue += $dueAt < $now ? 1 : 0;
            }

            $this->entityManager->persist($loan);
            ++$created;
        }

        return [$created, $overdue];
    }

    /**
     * @param list<string> $values
     *
     * @return string
     */
    private static function pick(array $values): string
    {
        return $values[array_rand($values)];
    }

    /**
     * @return string gültige ISBN-13 mit Prüfziffer
     *
     * @throws RandomException
     */
    private static function randomIsbn13(): string
    {
        $digits = '978'.str_pad((string) random_int(0, 999_999_999), 9, '0', \STR_PAD_LEFT);

        $sum = 0;
        foreach (str_split($digits) as $index => $digit) {
            $sum += (int) $digit * (0 === $index % 2 ? 1 : 3);
        }

        return $digits.((10 - $sum % 10) % 10);
    }

    /**
     * @param string $firstName
     * @param string $lastName
     *
     * @return string eindeutige E-Mail-Adresse unter example.com
     *
     * @throws RandomException
     */
    private static function email(string $firstName, string $lastName): string
    {
        $localPart = strtolower(strtr(sprintf('%s.%s', $firstName, $lastName), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']));

        return sprintf('%s.%s@example.com', $localPart, bin2hex(random_bytes(3)));
    }
}
