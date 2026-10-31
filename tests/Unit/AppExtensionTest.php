<?php

namespace App\Tests\Unit;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

final class AppExtensionTest extends TestCase
{
    public function testPosterIsResized(): void
    {
        self::assertSame(
            'https://m.media-amazon.com/images/M/MV5Babc@._V1_QL75_UX120_.jpg',
            AppExtension::poster('https://m.media-amazon.com/images/M/MV5Babc@._V1_.jpg'),
        );
        self::assertSame(
            'https://m.media-amazon.com/images/M/MV5Babc@._V1_QL75_UX300_.jpg',
            AppExtension::poster('https://m.media-amazon.com/images/M/MV5Babc@._V1_QL75_UX120_.jpg', 300),
        );
        self::assertSame('https://example.com/poster.jpg', AppExtension::poster('https://example.com/poster.jpg'));
        self::assertNull(AppExtension::poster(null));
    }

    public function testRuntime(): void
    {
        self::assertSame('2 h 07 min', AppExtension::runtime(127));
        self::assertSame('45 min', AppExtension::runtime(45));
        self::assertNull(AppExtension::runtime(null));
    }
}
