<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Loan;
use App\Service\LoanService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Verarbeitet POST /api/loans: bereitet die Ausleihe über den LoanService vor und speichert sie.
 *
 * @implements ProcessorInterface<Loan, Loan>
 */
final class LoanProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<Loan, Loan> $persistProcessor Standard-Processor von API Platform zum Speichern über Doctrine
     * @param LoanService                    $loanService      Geschäftslogik für Ausleihen
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private LoanService $loanService,
    ) {
    }

    /**
     * Wendet die Geschäftsregeln an und speichert die Ausleihe.
     *
     * @param Loan                 $data         die von API Platform geladene bzw. deserialisierte Ausleihe
     * @param Operation            $operation    die aktuelle API-Platform-Operation
     * @param array<string, mixed> $uriVariables Variablen aus der URI (z. B. id)
     * @param array<string, mixed> $context      Kontext von API Platform
     *
     * @return Loan die gespeicherte Ausleihe
     *
     * @throws \Symfony\Component\HttpKernel\Exception\ConflictHttpException
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Loan
    {
        $this->loanService->prepareLoan($data);

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
