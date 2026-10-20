<?php

namespace App\Command;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:create-dummy-books', description: 'Erstellt Dummy-Bücher in der Datenbank')]
class CreateDummyBooksCommand
{
    private const BATCH_SIZE = 100;

    private const ADJECTIVES = ['Verlorene', 'Dunkle', 'Stille', 'Letzte', 'Goldene', 'Vergessene', 'Ewige', 'Geheime'];
    private const NOUNS = ['Stadt', 'Reise', 'Insel', 'Nacht', 'Erinnerung', 'Grenze', 'Wahrheit', 'Spur'];
    private const FIRST_NAMES = ['Anna', 'Lukas', 'Marie', 'Felix', 'Sophie', 'Jonas', 'Lena', 'Paul'];
    private const LAST_NAMES = ['Müller', 'Schmidt', 'Weber', 'Fischer', 'Wagner', 'Becker', 'Hoffmann', 'Koch'];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Anzahl der zu erstellenden Bücher')] int $count = 50,
    ): int {
        if ($count < 1) {
            $io->error('Die Anzahl muss mindestens 1 sein.');

            return Command::INVALID;
        }

        for ($i = 1; $i <= $count; ++$i) {
            $Book = (new Book())
                ->setTitle(sprintf('Die %s %s', self::pick(self::ADJECTIVES), self::pick(self::NOUNS)))
                ->setAuthor(sprintf('%s %s', self::pick(self::FIRST_NAMES), self::pick(self::LAST_NAMES)))
                ->setIsbn(self::randomIsbn13())
                ->setAvailableCopies(random_int(0, 10));

            $this->entityManager->persist($Book);

            if (0 === $i % self::BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $io->success(sprintf('%d Dummy-Bücher erstellt.', $count));

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $values
     */
    private static function pick(array $values): string
    {
        return $values[array_rand($values)];
    }

    private static function randomIsbn13(): string
    {
        $digits = '978'.str_pad((string) random_int(0, 999_999_999), 9, '0', \STR_PAD_LEFT);

        $sum = 0;
        foreach (str_split($digits) as $index => $digit) {
            $sum += (int) $digit * (0 === $index % 2 ? 1 : 3);
        }

        return $digits.((10 - $sum % 10) % 10);
    }
}
