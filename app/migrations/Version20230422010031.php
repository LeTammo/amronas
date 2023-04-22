<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230422010031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE movie (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, imdb_id VARCHAR(255) NOT NULL, genre JSON DEFAULT NULL, year INT DEFAULT NULL, trailer_youtube_id VARCHAR(255) DEFAULT NULL, stream_provider JSON DEFAULT NULL, poster_url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE movie_list (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE movie_list_maintainer (movie_list_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_E11A84B01D3854A5 (movie_list_id), INDEX IDX_E11A84B0A76ED395 (user_id), PRIMARY KEY(movie_list_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE movie_list_subscriber (movie_list_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_42BBE1DB1D3854A5 (movie_list_id), INDEX IDX_42BBE1DBA76ED395 (user_id), PRIMARY KEY(movie_list_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE movie_list_entry (id INT AUTO_INCREMENT NOT NULL, movie_id INT NOT NULL, movie_list_id INT NOT NULL, added_by_id INT NOT NULL, time_added DATETIME NOT NULL, time_watched DATETIME DEFAULT NULL, INDEX IDX_1B42794F8F93B6FC (movie_id), INDEX IDX_1B42794F1D3854A5 (movie_list_id), INDEX IDX_1B42794F55B127A4 (added_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, old_id INT NOT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wordle_game (id INT AUTO_INCREMENT NOT NULL, solution_id INT NOT NULL, player_id INT NOT NULL, is_finished TINYINT(1) DEFAULT NULL, is_solved TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL, old_id INT NOT NULL, INDEX IDX_BA85E2811C0BE183 (solution_id), INDEX IDX_BA85E28199E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wordle_guess (id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, guessed_word VARCHAR(255) NOT NULL, info JSON NOT NULL, is_correct TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, old_id INT NOT NULL, INDEX IDX_4CFBDDF8E48FD905 (game_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wordle_solution (id INT AUTO_INCREMENT NOT NULL, correct_word VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, old_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE movie_list_maintainer ADD CONSTRAINT FK_E11A84B01D3854A5 FOREIGN KEY (movie_list_id) REFERENCES movie_list (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movie_list_maintainer ADD CONSTRAINT FK_E11A84B0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movie_list_subscriber ADD CONSTRAINT FK_42BBE1DB1D3854A5 FOREIGN KEY (movie_list_id) REFERENCES movie_list (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movie_list_subscriber ADD CONSTRAINT FK_42BBE1DBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movie_list_entry ADD CONSTRAINT FK_1B42794F8F93B6FC FOREIGN KEY (movie_id) REFERENCES movie (id)');
        $this->addSql('ALTER TABLE movie_list_entry ADD CONSTRAINT FK_1B42794F1D3854A5 FOREIGN KEY (movie_list_id) REFERENCES movie_list (id)');
        $this->addSql('ALTER TABLE movie_list_entry ADD CONSTRAINT FK_1B42794F55B127A4 FOREIGN KEY (added_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE wordle_game ADD CONSTRAINT FK_BA85E2811C0BE183 FOREIGN KEY (solution_id) REFERENCES wordle_solution (id)');
        $this->addSql('ALTER TABLE wordle_game ADD CONSTRAINT FK_BA85E28199E6F5DF FOREIGN KEY (player_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE wordle_guess ADD CONSTRAINT FK_4CFBDDF8E48FD905 FOREIGN KEY (game_id) REFERENCES wordle_game (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE movie_list_maintainer DROP FOREIGN KEY FK_E11A84B01D3854A5');
        $this->addSql('ALTER TABLE movie_list_maintainer DROP FOREIGN KEY FK_E11A84B0A76ED395');
        $this->addSql('ALTER TABLE movie_list_subscriber DROP FOREIGN KEY FK_42BBE1DB1D3854A5');
        $this->addSql('ALTER TABLE movie_list_subscriber DROP FOREIGN KEY FK_42BBE1DBA76ED395');
        $this->addSql('ALTER TABLE movie_list_entry DROP FOREIGN KEY FK_1B42794F8F93B6FC');
        $this->addSql('ALTER TABLE movie_list_entry DROP FOREIGN KEY FK_1B42794F1D3854A5');
        $this->addSql('ALTER TABLE movie_list_entry DROP FOREIGN KEY FK_1B42794F55B127A4');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE wordle_game DROP FOREIGN KEY FK_BA85E2811C0BE183');
        $this->addSql('ALTER TABLE wordle_game DROP FOREIGN KEY FK_BA85E28199E6F5DF');
        $this->addSql('ALTER TABLE wordle_guess DROP FOREIGN KEY FK_4CFBDDF8E48FD905');
        $this->addSql('DROP TABLE movie');
        $this->addSql('DROP TABLE movie_list');
        $this->addSql('DROP TABLE movie_list_maintainer');
        $this->addSql('DROP TABLE movie_list_subscriber');
        $this->addSql('DROP TABLE movie_list_entry');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE wordle_game');
        $this->addSql('DROP TABLE wordle_guess');
        $this->addSql('DROP TABLE wordle_solution');
    }
}
