<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260111065555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transactions ADD session_service_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4CDCAA2BD FOREIGN KEY (session_service_id) REFERENCES session_services (id)');
        $this->addSql('CREATE INDEX IDX_EAA81A4CDCAA2BD ON transactions (session_service_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4CDCAA2BD');
        $this->addSql('DROP INDEX IDX_EAA81A4CDCAA2BD ON transactions');
        $this->addSql('ALTER TABLE transactions DROP session_service_id');
    }
}
