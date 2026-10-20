<?php

namespace App\Tests\Api;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\Member;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

class LoanApiTest extends WebTestCase
{
    use ClockSensitiveTrait;

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    /** @var list<object> */
    private array $created = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        // Tests laufen gegen die normale Datenbank, daher angelegte Daten wieder entfernen
        foreach ($this->em->getRepository(Loan::class)->findBy(['book' => array_filter($this->created, fn ($e) => $e instanceof Book)]) as $loan) {
            $this->em->remove($loan);
        }
        foreach ($this->created as $entity) {
            $this->em->remove($this->em->getReference($entity::class, $entity->getId()));
        }
        $this->em->flush();

        parent::tearDown();
    }

    public function testGetCollectionReturnsArray(): void
    {
        $this->client->request('GET', '/api/loans', server: ['HTTP_ACCEPT' => 'application/ld+json']);

        self::assertResponseStatusCodeSame(200);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data['member'] ?? null);
    }

    public function testPostLoanDecrementsAvailableCopies(): void
    {
        $book = $this->createBook(availableCopies: 2);
        $member = $this->createMember();

        $this->postLoan($book, $member);

        self::assertResponseStatusCodeSame(201);

        $this->em->clear();
        $book = $this->em->find(Book::class, $book->getId());
        self::assertSame(1, $book->getAvailableCopies());
        self::assertCount(1, $book->getLoans());
    }

    public function testPostLoanSetsDefaultDates(): void
    {
        self::mockTime('2026-01-15 10:00:00');

        $book = $this->createBook(availableCopies: 1);
        $member = $this->createMember();

        // vom Client gesendete Werte müssen überschrieben werden
        $this->postLoan($book, $member, [
            'loanedAt' => '2000-01-01T00:00:00+00:00',
            'dueAt' => '2000-01-02T00:00:00+00:00',
            'returnedAt' => '2000-01-03T00:00:00+00:00',
        ]);

        self::assertResponseStatusCodeSame(201);

        $this->em->clear();
        $loan = $this->em->getRepository(Loan::class)->findOneBy(['book' => $book->getId()]);
        self::assertNotNull($loan);
        self::assertSame('2026-01-15 10:00:00', $loan->getLoanedAt()->format('Y-m-d H:i:s'));
        self::assertSame('2026-01-29 10:00:00', $loan->getDueAt()->format('Y-m-d H:i:s'));
        self::assertEquals($loan->getLoanedAt()->add(new \DateInterval(Loan::LOAN_PERIOD)), $loan->getDueAt());
        self::assertNull($loan->getReturnedAt());
    }

    public function testPostLoanReturnsConflictWhenNoCopiesAvailable(): void
    {
        $book = $this->createBook(availableCopies: 0);
        $member = $this->createMember();

        $this->postLoan($book, $member);

        self::assertResponseStatusCodeSame(409);

        $this->em->clear();
        $book = $this->em->find(Book::class, $book->getId());
        self::assertSame(0, $book->getAvailableCopies());
        self::assertCount(0, $book->getLoans());
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function postLoan(Book $book, Member $member, array $extra = []): void
    {
        $this->client->request(
            'POST',
            '/api/loans',
            server: [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            content: json_encode([
                'book' => '/api/books/'.$book->getId(),
                'member' => '/api/members/'.$member->getId(),
                ...$extra,
            ]),
        );
    }

    private function createBook(int $availableCopies): Book
    {
        $book = (new Book())
            ->setTitle('Testbuch')
            ->setAuthor('Test Autor')
            ->setIsbn('978-3-16-148410-0')
            ->setAvailableCopies($availableCopies);

        return $this->persist($book);
    }

    private function createMember(): Member
    {
        $member = (new Member())
            ->setName('Test Mitglied')
            ->setEmail('test@example.com')
            ->setMembershipDate(new \DateTimeImmutable());

        return $this->persist($member);
    }

    /**
     * @template T of object
     *
     * @param T $entity
     *
     * @return T
     */
    private function persist(object $entity): object
    {
        $this->em->persist($entity);
        $this->em->flush();
        $this->created[] = $entity;

        return $entity;
    }
}
