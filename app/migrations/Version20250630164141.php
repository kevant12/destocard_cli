<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250630164141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE pokemon_card ADD extension_id INT NOT NULL, DROP extension
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pokemon_card ADD CONSTRAINT FK_2ABDE690812D5EB FOREIGN KEY (extension_id) REFERENCES extension (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2ABDE690812D5EB ON pokemon_card (extension_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE pokemon_card DROP FOREIGN KEY FK_2ABDE690812D5EB
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_2ABDE690812D5EB ON pokemon_card
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE pokemon_card ADD extension VARCHAR(255) DEFAULT NULL, DROP extension_id
        SQL);
    }
}
