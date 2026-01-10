<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260110020147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE operator_balances (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, balance NUMERIC(15, 2) NOT NULL, currency VARCHAR(10) NOT NULL, updated_at DATETIME NOT NULL, editable TINYINT NOT NULL, notes LONGTEXT DEFAULT NULL, operator_id INT NOT NULL, INDEX IDX_95819135584598A3 (operator_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE operator_balances ADD CONSTRAINT FK_95819135584598A3 FOREIGN KEY (operator_id) REFERENCES operators (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE operator_balances DROP FOREIGN KEY FK_95819135584598A3');
        $this->addSql('DROP TABLE operator_balances');
    }
}
