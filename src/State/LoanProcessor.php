<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Loan;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @implements ProcessorInterface<Loan, Loan>
 */
final class LoanProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private ClockInterface $clock,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Loan
    {
        $loanedAt = $this->clock->now();

        $data
            ->setLoanedAt($loanedAt)
            ->setDueAt($loanedAt->add(new \DateInterval(Loan::LOAN_PERIOD)))
            ->setReturnedAt(null);

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
