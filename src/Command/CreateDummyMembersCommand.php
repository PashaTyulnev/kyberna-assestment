<?php

namespace App\Command;

use App\Entity\Member;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:create-dummy-members', description: 'Erstellt Dummy-Mitglieder in der Datenbank')]
class CreateDummyMembersCommand
{
    private const int BATCH_SIZE = 100;

    private const array FIRST_NAMES = ['Anna', 'Lukas', 'Marie', 'Felix', 'Sophie', 'Jonas', 'Lena', 'Paul'];
    private const array LAST_NAMES = ['Müller', 'Schmidt', 'Weber', 'Fischer', 'Wagner', 'Becker', 'Hoffmann', 'Koch'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Anzahl der zu erstellenden Mitglieder')] int $count = 50,
    ): int {
        if ($count < 1) {
            $io->error('Die Anzahl muss mindestens 1 sein.');

            return Command::INVALID;
        }

        $utc = new DateTimeZone('UTC');

        for ($i = 1; $i <= $count; ++$i) {
            $firstName = self::pick(self::FIRST_NAMES);
            $lastName = self::pick(self::LAST_NAMES);

            $member = new Member()
                ->setName(sprintf('%s %s', $firstName, $lastName))
                ->setEmail(self::email($firstName, $lastName))
                ->setMembershipDate(new DateTimeImmutable(sprintf('-%d days', random_int(0, 3 * 365)), $utc));

            $this->entityManager->persist($member);

            if (0 === $i % self::BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $io->success(sprintf('%d Dummy-Mitglieder erstellt.', $count));

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $values
     */
    private static function pick(array $values): string
    {
        return $values[array_rand($values)];
    }

    private static function email(string $firstName, string $lastName): string
    {
        $localPart = strtolower(strtr(sprintf('%s.%s', $firstName, $lastName), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']));

        return sprintf('%s.%s@example.com', $localPart, bin2hex(random_bytes(3)));
    }
}
