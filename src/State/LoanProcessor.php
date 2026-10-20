<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CreateLoanRequest;
use App\Entity\Loan;
use App\Repository\BookRepository;
use App\Repository\MemberRepository;
use App\Service\LoanService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Verarbeitet POST /api/loans: lädt Buch und Mitglied anhand der IDs, bereitet die Ausleihe über den LoanService vor und speichert sie.
 *
 * @implements ProcessorInterface<CreateLoanRequest, Loan>
 */
final class LoanProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<Loan, Loan> $persistProcessor Standard-Processor von API Platform zum Speichern über Doctrine
     * @param LoanService                    $loanService      Geschäftslogik für Ausleihen
     * @param BookRepository                 $bookRepository   lädt das Buch zur book_id
     * @param MemberRepository               $memberRepository lädt das Mitglied zur member_id
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private LoanService $loanService,
        private BookRepository $bookRepository,
        private MemberRepository $memberRepository,
    ) {
    }

    /**
     * Legt aus dem validierten Request die Ausleihe an, wendet die Geschäftsregeln an und speichert sie.
     *
     * @param CreateLoanRequest    $data         von API Platform deserialisierter und validierter Request-Body
     * @param Operation            $operation    die aktuelle API-Platform-Operation
     * @param array<string, mixed> $uriVariables Variablen aus der URI
     * @param array<string, mixed> $context      Kontext von API Platform
     *
     * @return Loan die gespeicherte Ausleihe
     *
     * @throws NotFoundHttpException                                         wenn Buch oder Mitglied nicht existiert
     * @throws \Symfony\Component\HttpKernel\Exception\ConflictHttpException wenn keine Kopie verfügbar ist
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Loan
    {
        $book = $this->bookRepository->find($data->bookId)
            ?? throw new NotFoundHttpException(sprintf('Book %d not found.', $data->bookId));
        $member = $this->memberRepository->find($data->memberId)
            ?? throw new NotFoundHttpException(sprintf('Member %d not found.', $data->memberId));

        $loan = (new Loan())->setBook($book)->setMember($member);

        $this->loanService->prepareLoan($loan);

        return $this->persistProcessor->process($loan, $operation, $uriVariables, $context);
    }
}
