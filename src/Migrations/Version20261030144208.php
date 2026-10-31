<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261030144208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE availability (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, offers CLOB NOT NULL, fetched_at DATETIME NOT NULL, country VARCHAR(2) NOT NULL, title_id INTEGER NOT NULL, CONSTRAINT FK_3FB7A2BFA9F87BD FOREIGN KEY (title_id) REFERENCES title (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3FB7A2BFA9F87BD5373C966 ON availability (title_id, country)');
        $this->addSql('CREATE INDEX IDX_3FB7A2BFA9F87BD ON availability (title_id)');
        $this->addSql('CREATE TABLE list_entry (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, added_at DATETIME NOT NULL, watched_at DATETIME DEFAULT NULL, priority VARCHAR(255) NOT NULL, list_id INTEGER NOT NULL, title_id INTEGER NOT NULL, added_by_id INTEGER DEFAULT NULL, CONSTRAINT FK_F2B209733DAE168B FOREIGN KEY (list_id) REFERENCES watch_list (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F2B20973A9F87BD FOREIGN KEY (title_id) REFERENCES title (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F2B2097355B127A4 FOREIGN KEY (added_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F2B209733DAE168BA9F87BD ON list_entry (list_id, title_id)');
        $this->addSql('CREATE INDEX IDX_F2B209733DAE168B ON list_entry (list_id)');
        $this->addSql('CREATE INDEX IDX_F2B20973A9F87BD ON list_entry (title_id)');
        $this->addSql('CREATE INDEX IDX_F2B2097355B127A4 ON list_entry (added_by_id)');
        $this->addSql('CREATE TABLE list_invite (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, token_hash VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, can_edit BOOLEAN NOT NULL, used_by_id INTEGER DEFAULT NULL, list_id INTEGER NOT NULL, created_by_id INTEGER NOT NULL, CONSTRAINT FK_5E32D2F94C2B72A8 FOREIGN KEY (used_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5E32D2F93DAE168B FOREIGN KEY (list_id) REFERENCES watch_list (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5E32D2F9B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5E32D2F9B3BC57DA ON list_invite (token_hash)');
        $this->addSql('CREATE INDEX IDX_5E32D2F94C2B72A8 ON list_invite (used_by_id)');
        $this->addSql('CREATE INDEX IDX_5E32D2F93DAE168B ON list_invite (list_id)');
        $this->addSql('CREATE INDEX IDX_5E32D2F9B03A8386 ON list_invite (created_by_id)');
        $this->addSql('CREATE TABLE list_member (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, joined_at DATETIME NOT NULL, can_edit BOOLEAN NOT NULL, list_id INTEGER NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_E93438563DAE168B FOREIGN KEY (list_id) REFERENCES watch_list (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E9343856A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E93438563DAE168BA76ED395 ON list_member (list_id, user_id)');
        $this->addSql('CREATE INDEX IDX_E93438563DAE168B ON list_member (list_id)');
        $this->addSql('CREATE INDEX IDX_E9343856A76ED395 ON list_member (user_id)');
        $this->addSql('CREATE TABLE title (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, original_name VARCHAR(255) DEFAULT NULL, year INTEGER DEFAULT NULL, end_year INTEGER DEFAULT NULL, runtime INTEGER DEFAULT NULL, seasons INTEGER DEFAULT NULL, genres CLOB NOT NULL, plot CLOB DEFAULT NULL, rating DOUBLE PRECISION DEFAULT NULL, poster_url VARCHAR(500) DEFAULT NULL, "cast" VARCHAR(255) DEFAULT NULL, details_fetched_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, imdb_id VARCHAR(12) NOT NULL, type VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2B36786B53B538EB ON title (imdb_id)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(30) NOT NULL, email VARCHAR(180) DEFAULT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, locale VARCHAR(2) NOT NULL, country VARCHAR(2) NOT NULL, services CLOB NOT NULL, sort_field VARCHAR(255) NOT NULL, sort_descending BOOLEAN NOT NULL, list_view VARCHAR(255) NOT NULL, recent_lists CLOB NOT NULL, setup_completed BOOLEAN NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON "user" (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE TABLE watch_list (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(80) NOT NULL, type VARCHAR(255) NOT NULL, country VARCHAR(2) NOT NULL, created_at DATETIME NOT NULL, owner_id INTEGER NOT NULL, CONSTRAINT FK_152B584B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_152B584B7E3C61F9 ON watch_list (owner_id)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
        $this->addSql('CREATE TABLE cache_items (item_id CLOB NOT NULL, item_data BLOB NOT NULL, item_lifetime INTEGER UNSIGNED DEFAULT NULL, item_time INTEGER UNSIGNED NOT NULL, PRIMARY KEY (item_id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE availability');
        $this->addSql('DROP TABLE list_entry');
        $this->addSql('DROP TABLE list_invite');
        $this->addSql('DROP TABLE list_member');
        $this->addSql('DROP TABLE title');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE watch_list');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE cache_items');
    }
}
