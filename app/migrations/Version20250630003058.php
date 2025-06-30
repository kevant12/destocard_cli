<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250630003058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE orders ADD delivery_method VARCHAR(100) DEFAULT NULL, ADD shipping_cost DOUBLE PRECISION DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE products DROP number, DROP extension, DROP rarity, DROP type
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE orders DROP delivery_method, DROP shipping_cost
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE products ADD number INT NOT NULL, ADD extension VARCHAR(100) NOT NULL, ADD rarity VARCHAR(50) NOT NULL, ADD type VARCHAR(50) NOT NULL
        SQL);
    }
}
