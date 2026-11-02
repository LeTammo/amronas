<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** The ids of a user's lists in the order they dragged them into. */
final readonly class ListOrder
{
    /** @param list<int|string> $ids */
    public function __construct(
        #[Assert\Count(max: 500)]
        #[Assert\All([new Assert\Type('digit')])]
        public array $ids = [],
    ) {
    }
}
