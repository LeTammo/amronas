<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261102224523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remember where a title\'s details come from (streaming API, or IMDb/TMDB as fallback)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE title ADD COLUMN details_source VARCHAR(10) DEFAULT NULL');
        // Until now only the streaming API set details_fetched_at
        $this->addSql("UPDATE title SET details_source = 'motn' WHERE details_fetched_at IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
        // A plain drop instead of rebuilding the title table, which would cascade to the list entries
        $this->addSql('ALTER TABLE title DROP COLUMN details_source');
    }
}
