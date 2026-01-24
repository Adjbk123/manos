<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260124165710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE loans (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(15, 2) NOT NULL, remaining_amount NUMERIC(15, 2) NOT NULL, type VARCHAR(10) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, repaid_at DATETIME DEFAULT NULL, notes LONGTEXT DEFAULT NULL, lender_id INT NOT NULL, borrower_id INT NOT NULL, operator_id INT DEFAULT NULL, INDEX IDX_82C24DBC855D3E3D (lender_id), INDEX IDX_82C24DBC11CE312B (borrower_id), INDEX IDX_82C24DBC584598A3 (operator_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC855D3E3D FOREIGN KEY (lender_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC11CE312B FOREIGN KEY (borrower_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC584598A3 FOREIGN KEY (operator_id) REFERENCES operators (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC855D3E3D');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC11CE312B');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC584598A3');
        $this->addSql('DROP TABLE loans');
    }
}
