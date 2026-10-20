<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request-Body für POST /api/loans: {"book_id": 1, "member_id": 2}.
 */
final readonly class CreateLoanRequest
{
    /**
     * @param int|null $bookId   ID des auszuleihenden Buchs
     * @param int|null $memberId ID des ausleihenden Mitglieds
     */
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        #[SerializedName('book_id')]
        public ?int $bookId = null,

        #[Assert\NotNull]
        #[Assert\Positive]
        #[SerializedName('member_id')]
        public ?int $memberId = null,
    ) {
    }
}
