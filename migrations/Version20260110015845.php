<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260110015845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE operation_types (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(50) DEFAULT NULL, description LONGTEXT DEFAULT NULL, operator_id INT NOT NULL, INDEX IDX_DE175ABF584598A3 (operator_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE operators (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, logo VARCHAR(255) DEFAULT NULL, prefix VARCHAR(50) DEFAULT NULL, comments LONGTEXT DEFAULT NULL, status TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ussd_codes (id INT AUTO_INCREMENT NOT NULL, template VARCHAR(255) NOT NULL, parameters JSON DEFAULT NULL, method VARCHAR(50) NOT NULL, is_editable TINYINT NOT NULL, notes LONGTEXT DEFAULT NULL, operator_id INT NOT NULL, operation_type_id INT NOT NULL, INDEX IDX_B9B5AB4D584598A3 (operator_id), INDEX IDX_B9B5AB4D668D0C5E (operation_type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE operation_types ADD CONSTRAINT FK_DE175ABF584598A3 FOREIGN KEY (operator_id) REFERENCES operators (id)');
        $this->addSql('ALTER TABLE ussd_codes ADD CONSTRAINT FK_B9B5AB4D584598A3 FOREIGN KEY (operator_id) REFERENCES operators (id)');
        $this->addSql('ALTER TABLE ussd_codes ADD CONSTRAINT FK_B9B5AB4D668D0C5E FOREIGN KEY (operation_type_id) REFERENCES operation_types (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE operation_types DROP FOREIGN KEY FK_DE175ABF584598A3');
        $this->addSql('ALTER TABLE ussd_codes DROP FOREIGN KEY FK_B9B5AB4D584598A3');
        $this->addSql('ALTER TABLE ussd_codes DROP FOREIGN KEY FK_B9B5AB4D668D0C5E');
        $this->addSql('DROP TABLE operation_types');
        $this->addSql('DROP TABLE operators');
        $this->addSql('DROP TABLE ussd_codes');
    }
}
