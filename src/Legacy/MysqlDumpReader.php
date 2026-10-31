<?php

namespace App\Legacy;

/**
 * Reads the rows of the INSERT statements in a MySQL/phpMyAdmin dump, without needing MySQL.
 * Only used by the one-time import of the old amronas data (see ImportLegacyCommand).
 */
final class MysqlDumpReader
{
    /** @var array<string, list<array<string, string|null>>> */
    private array $tables = [];

    public function __construct(string $file)
    {
        $sql = file_get_contents($file);
        if (false === $sql) {
            throw new \RuntimeException(\sprintf('Cannot read "%s".', $file));
        }

        preg_match_all('/^INSERT INTO `(\w+)` \(([^)]*)\) VALUES\s*/m', $sql, $matches, \PREG_OFFSET_CAPTURE | \PREG_SET_ORDER);
        foreach ($matches as $match) {
            $columns = array_map(static fn (string $c) => trim($c, " `"), explode(',', $match[2][0]));
            $offset = $match[0][1] + \strlen($match[0][0]);
            foreach ($this->parseValues($sql, $offset) as $values) {
                $this->tables[$match[1][0]][] = array_combine($columns, $values);
            }
        }
    }

    /** @return list<array<string, string|null>> */
    public function rows(string $table): array
    {
        return $this->tables[$table] ?? [];
    }

    /** @return list<list<string|null>> the value tuples of one INSERT statement, up to its ";" */
    private function parseValues(string $sql, int $i): array
    {
        $rows = [];
        $length = \strlen($sql);
        while ($i < $length) {
            $char = $sql[$i];
            if ('(' === $char) {
                [$row, $i] = $this->parseTuple($sql, $i + 1);
                $rows[] = $row;
            } elseif (';' === $char) {
                break;
            } else {
                ++$i;
            }
        }

        return $rows;
    }

    /** @return array{list<string|null>, int} */
    private function parseTuple(string $sql, int $i): array
    {
        $values = [];
        $length = \strlen($sql);
        while ($i < $length) {
            $char = $sql[$i];
            if ("'" === $char) {
                $value = '';
                for (++$i; $i < $length; ++$i) {
                    $c = $sql[$i];
                    if ('\\' === $c) {
                        $next = $sql[++$i];
                        $value .= match ($next) { 'n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0", default => $next };
                    } elseif ("'" === $c) {
                        if ("'" === ($sql[$i + 1] ?? '')) {
                            $value .= "'";
                            ++$i;
                        } else {
                            break;
                        }
                    } else {
                        $value .= $c;
                    }
                }
                $values[] = $value;
                ++$i;
            } elseif (')' === $char) {
                return [$values, $i + 1];
            } elseif (',' === $char || ctype_space($char)) {
                ++$i;
            } else {
                preg_match('/\G[^,)\s]+/', $sql, $m, 0, $i);
                $values[] = 'NULL' === $m[0] ? null : $m[0];
                $i += \strlen($m[0]);
            }
        }

        throw new \RuntimeException('Unexpected end of dump.');
    }
}
