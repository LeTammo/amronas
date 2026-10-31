<?php

namespace App\Dto;

use App\Enum\ListView;
use App\Enum\SortField;
use App\Enum\TitleType;

/** Filter, sort and view options of a list page, bound from the query string. */
final readonly class ListFilter
{
    public function __construct(
        public ?TitleType $type = null,
        public bool $short = false,
        public ?SortField $sort = null,
        public ?string $dir = null,
        public ?ListView $view = null,
        /** Set by every filter link, so "all filters off" can be told apart from "no filters given" (= use the remembered ones). */
        public bool $f = false,
    ) {
    }

    /**
     * Query parameters for a link with some filters changed (null or false removes a filter).
     *
     * @param array<string, mixed> $changes
     *
     * @return array<string, string>
     */
    public function query(array $changes = []): array
    {
        $query = array_merge([
            'f' => true,
            'type' => $this->type?->value,
            'short' => $this->short,
        ], $changes);

        return array_map(
            static fn (mixed $v) => $v instanceof \BackedEnum ? (string) $v->value : (true === $v ? '1' : (string) $v),
            array_filter($query, static fn (mixed $v) => null !== $v && false !== $v),
        );
    }

    /** Only the filters, for remembering them in the session. */
    public function withoutDisplayOptions(): self
    {
        return new self($this->type, $this->short, f: true);
    }
}
