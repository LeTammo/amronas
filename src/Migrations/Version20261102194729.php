<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261102194729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lists are ordered by the user instead of by last opened';
    }

    public function up(Schema $schema): void
    {
        // A plain rename instead of rebuilding the user table, which would cascade to members and entries
        $this->addSql('ALTER TABLE "user" RENAME COLUMN recent_lists TO list_order');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" RENAME COLUMN list_order TO recent_lists');
    }
}
