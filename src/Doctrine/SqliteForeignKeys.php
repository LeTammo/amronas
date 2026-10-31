<?php

namespace App\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsMiddleware;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys;
use Doctrine\DBAL\Driver\Middleware;

/** SQLite ignores foreign keys (and ON DELETE CASCADE) unless they are switched on per connection. */
#[AsMiddleware]
final class SqliteForeignKeys implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new EnableForeignKeys()->wrap($driver);
    }
}
