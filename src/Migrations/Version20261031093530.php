<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261031093530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE watch_list ADD COLUMN icon VARCHAR(40) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__watch_list AS SELECT id, name, type, country, created_at, owner_id FROM watch_list');
        $this->addSql('DROP TABLE watch_list');
        $this->addSql('CREATE TABLE watch_list (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(80) NOT NULL, type VARCHAR(255) NOT NULL, country VARCHAR(2) NOT NULL, created_at DATETIME NOT NULL, owner_id INTEGER NOT NULL, CONSTRAINT FK_152B584B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO watch_list (id, name, type, country, created_at, owner_id) SELECT id, name, type, country, created_at, owner_id FROM __temp__watch_list');
        $this->addSql('DROP TABLE __temp__watch_list');
        $this->addSql('CREATE INDEX IDX_152B584B7E3C61F9 ON watch_list (owner_id)');
    }
}
