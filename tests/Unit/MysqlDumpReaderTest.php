<?php

namespace App\Tests\Unit;

use App\Legacy\MysqlDumpReader;
use PHPUnit\Framework\TestCase;

final class MysqlDumpReaderTest extends TestCase
{
    public function testReadsRowsOfInsertStatements(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'dump');
        file_put_contents($file, <<<'SQL'
            CREATE TABLE `movie` (`id` int NOT NULL);
            INSERT INTO `movie` (`id`, `name`, `genre`, `year`) VALUES
            (1, 'Single\'s Inferno', '[\"Reality\"]', NULL),
            (2, 'It''s 🎬, (a) test', '[]', 2024);
            INSERT INTO `user` (`id`, `username`) VALUES
            (3, 'robin');
            SQL);

        $reader = new MysqlDumpReader($file);
        unlink($file);

        self::assertSame([
            ['id' => '1', 'name' => "Single's Inferno", 'genre' => '["Reality"]', 'year' => null],
            ['id' => '2', 'name' => "It's 🎬, (a) test", 'genre' => '[]', 'year' => '2024'],
        ], $reader->rows('movie'));
        self::assertSame([['id' => '3', 'username' => 'robin']], $reader->rows('user'));
        self::assertSame([], $reader->rows('missing'));
    }
}
