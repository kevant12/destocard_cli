<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250630094234 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE orders ADD paid_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD stripe_payment_intent_id VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE products CHANGE pokemon_card_id pokemon_card_id INT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE orders DROP paid_at, DROP stripe_payment_intent_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE products CHANGE pokemon_card_id pokemon_card_id INT NOT NULL
        SQL);
    }
}
