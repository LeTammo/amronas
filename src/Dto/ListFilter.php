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
        /** @var list<string> genre keys; a title matches if it has any of them */
        public array $genre = [],
        /** Set by every filter link, so "all filters off" can be told apart from "no filters given" (= use the remembered ones). */
        public bool $f = false,
    ) {
    }

    /**
     * Query parameters for a link with some filters changed (null or false removes a filter).
     *
     * @param array<string, mixed> $changes
     *
     * @return array<string, string|list<string>>
     */
    public function query(array $changes = []): array
    {
        $query = array_merge([
            'f' => true,
            'type' => $this->type?->value,
            'short' => $this->short,
            'genre' => $this->genre,
        ], $changes);

        return array_map(
            static fn (mixed $v) => match (true) {
                $v instanceof \BackedEnum => (string) $v->value,
                \is_array($v) => array_values($v),
                true === $v => '1',
                default => (string) $v,
            },
            array_filter($query, static fn (mixed $v) => null !== $v && false !== $v && [] !== $v),
        );
    }

    /** Query parameters for a link that switches one genre on or off. */
    public function toggleGenre(string $genre): array
    {
        $genres = \in_array($genre, $this->genre, true) ? array_diff($this->genre, [$genre]) : [...$this->genre, $genre];

        return $this->query(['genre' => array_values($genres)]);
    }

    /** Only the filters, for remembering them in the session. */
    public function withoutDisplayOptions(): self
    {
        return new self($this->type, $this->short, genre: $this->genre, f: true);
    }
}
