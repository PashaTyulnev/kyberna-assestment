<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use App\Dto\CreateLoanRequest;
use App\Repository\LoanRepository;
use App\State\LoanProcessor;
use App\State\ReturnLoanProcessor;
use Doctrine\ORM\Mapping as ORM;

/**
 * Eine Ausleihe eines Buchs durch ein Mitglied.
 */
#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(input: CreateLoanRequest::class, processor: LoanProcessor::class),
        new Post(
            uriTemplate: '/loans/{id}/return',
            status: 200,
            openapi: new OpenApiOperation(
                responses: [
                    '200' => new OpenApiResponse(description: 'Loan marked as returned'),
                    '404' => new OpenApiResponse(description: 'Loan not found'),
                    '409' => new OpenApiResponse(description: 'Loan already returned'),
                ],
                summary: 'Mark a loan as returned.',
                description: 'Sets returnedAt and increments availableCopies of the book.',
            ),
            input: false,
            name: 'loan_return',
            processor: ReturnLoanProcessor::class,
            throwOnNotFound: true,
        ),
    ],
)]
class Loan
{
    public const string LOAN_PERIOD = 'P2W';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $book = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Member $member = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $loanedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dueAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $returnedAt = null;

    /**
     * Gibt die ID zurück.
     *
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gibt das ausgeliehene Buch zurück.
     *
     * @return ?Book
     */
    public function getBook(): ?Book
    {
        return $this->book;
    }

    /**
     * Setzt das ausgeliehene Buch.
     *
     * @param ?Book $book
     *
     * @return static
     */
    public function setBook(?Book $book): static
    {
        $this->book = $book;

        return $this;
    }

    /**
     * Gibt das ausleihende Mitglied zurück.
     *
     * @return ?Member
     */
    public function getMember(): ?Member
    {
        return $this->member;
    }

    /**
     * Setzt das ausleihende Mitglied.
     *
     * @param ?Member $member
     *
     * @return static
     */
    public function setMember(?Member $member): static
    {
        $this->member = $member;

        return $this;
    }

    /**
     * Gibt den Ausleihzeitpunkt zurück.
     *
     * @return ?\DateTimeImmutable
     */
    public function getLoanedAt(): ?\DateTimeImmutable
    {
        return $this->loanedAt;
    }

    /**
     * Setzt den Ausleihzeitpunkt.
     *
     * @param \DateTimeImmutable $loanedAt
     *
     * @return static
     */
    public function setLoanedAt(\DateTimeImmutable $loanedAt): static
    {
        $this->loanedAt = $loanedAt;

        return $this;
    }

    /**
     * Gibt das Fälligkeitsdatum zurück.
     *
     * @return ?\DateTimeImmutable
     */
    public function getDueAt(): ?\DateTimeImmutable
    {
        return $this->dueAt;
    }

    /**
     * Setzt das Fälligkeitsdatum.
     *
     * @param \DateTimeImmutable $dueAt
     *
     * @return static
     */
    public function setDueAt(\DateTimeImmutable $dueAt): static
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    /**
     * Gibt den Rückgabezeitpunkt (null, solange nicht zurückgegeben) zurück.
     *
     * @return ?\DateTimeImmutable
     */
    public function getReturnedAt(): ?\DateTimeImmutable
    {
        return $this->returnedAt;
    }

    /**
     * Setzt den Rückgabezeitpunkt (null, solange nicht zurückgegeben).
     *
     * @param ?\DateTimeImmutable $returnedAt
     *
     * @return static
     */
    public function setReturnedAt(?\DateTimeImmutable $returnedAt): static
    {
        $this->returnedAt = $returnedAt;

        return $this;
    }
}
