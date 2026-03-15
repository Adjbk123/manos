<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260315112709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_price_history (id INT AUTO_INCREMENT NOT NULL, price NUMERIC(12, 2) NOT NULL, effective_from DATETIME NOT NULL, product_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_9671A4E54584665A (product_id), INDEX IDX_9671A4E5A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE product_price_history ADD CONSTRAINT FK_9671A4E54584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_price_history ADD CONSTRAINT FK_9671A4E5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE product ADD selling_price NUMERIC(12, 2) DEFAULT NULL, ADD alert_threshold INT NOT NULL, ADD is_active TINYINT NOT NULL');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY `FK_EAA81A4CDCAA2BD`');
        $this->addSql('DROP INDEX IDX_EAA81A4CDCAA2BD ON transactions');
        $this->addSql('ALTER TABLE transactions DROP session_service_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_price_history DROP FOREIGN KEY FK_9671A4E54584665A');
        $this->addSql('ALTER TABLE product_price_history DROP FOREIGN KEY FK_9671A4E5A76ED395');
        $this->addSql('DROP TABLE product_price_history');
        $this->addSql('ALTER TABLE product DROP selling_price, DROP alert_threshold, DROP is_active');
        $this->addSql('ALTER TABLE transactions ADD session_service_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT `FK_EAA81A4CDCAA2BD` FOREIGN KEY (session_service_id) REFERENCES session_services (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_EAA81A4CDCAA2BD ON transactions (session_service_id)');
    }
}
