<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250630144725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE extension (id INT AUTO_INCREMENT NOT NULL, serie_id INT NOT NULL, api_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, total_cards_main INT NOT NULL, total_cards_secret INT NOT NULL, UNIQUE INDEX UNIQ_9FB73D7754963938 (api_id), INDEX IDX_9FB73D77D94388BD (serie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE serie (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_AA3A93345E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE extension ADD CONSTRAINT FK_9FB73D77D94388BD FOREIGN KEY (serie_id) REFERENCES serie (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pokemon_card ADD category VARCHAR(255) NOT NULL, ADD special_type VARCHAR(255) DEFAULT NULL, ADD rarity_symbol VARCHAR(255) NOT NULL, ADD rarity_text VARCHAR(255) NOT NULL, ADD is_reverse_possible TINYINT(1) NOT NULL, ADD sub_serie VARCHAR(255) DEFAULT NULL, DROP star_rating, DROP holo, DROP reverse, DROP created_at, DROP updated_at, CHANGE rarity api_id VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_2ABDE69054963938 ON pokemon_card (api_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE extension DROP FOREIGN KEY FK_9FB73D77D94388BD
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE extension
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE serie
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_2ABDE69054963938 ON pokemon_card
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pokemon_card ADD rarity VARCHAR(255) NOT NULL, ADD star_rating INT NOT NULL, ADD reverse TINYINT(1) NOT NULL, ADD created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', ADD updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', DROP api_id, DROP category, DROP special_type, DROP rarity_symbol, DROP rarity_text, DROP sub_serie, CHANGE is_reverse_possible holo TINYINT(1) NOT NULL
        SQL);
    }
}
