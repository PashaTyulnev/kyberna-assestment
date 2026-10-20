<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Loan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository für den Datenbankzugriff auf Ausleihen.
 *
 * @extends ServiceEntityRepository<Loan>
 */
class LoanRepository extends ServiceEntityRepository
{
    /**
     * Registriert das Repository für die Entity Loan.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loan::class);
    }

    /**
     * Findet alle Ausleihen, die nicht zurückgegeben sind und deren Fälligkeitsdatum vor dem Stichtag liegt.
     *
     * @param \DateTimeImmutable $now Stichtag
     *
     * @return list<Loan>
     */
    public function findOverdue(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.returnedAt IS NULL')
            ->andWhere('l.dueAt < :now')
            ->setParameter('now', $now)
            ->orderBy('l.dueAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
