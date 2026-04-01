<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331225937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_prices (id INT AUTO_INCREMENT NOT NULL, price NUMERIC(12, 2) NOT NULL, product_id INT NOT NULL, sale_zone_id INT NOT NULL, INDEX IDX_86B72CFD4584665A (product_id), INDEX IDX_86B72CFDEA32FD39 (sale_zone_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sale_zones (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE product_prices ADD CONSTRAINT FK_86B72CFD4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_prices ADD CONSTRAINT FK_86B72CFDEA32FD39 FOREIGN KEY (sale_zone_id) REFERENCES sale_zones (id)');
        $this->addSql('ALTER TABLE sales ADD sale_zone_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sales ADD CONSTRAINT FK_6B817044EA32FD39 FOREIGN KEY (sale_zone_id) REFERENCES sale_zones (id)');
        $this->addSql('CREATE INDEX IDX_6B817044EA32FD39 ON sales (sale_zone_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_prices DROP FOREIGN KEY FK_86B72CFD4584665A');
        $this->addSql('ALTER TABLE product_prices DROP FOREIGN KEY FK_86B72CFDEA32FD39');
        $this->addSql('DROP TABLE product_prices');
        $this->addSql('DROP TABLE sale_zones');
        $this->addSql('ALTER TABLE sales DROP FOREIGN KEY FK_6B817044EA32FD39');
        $this->addSql('DROP INDEX IDX_6B817044EA32FD39 ON sales');
        $this->addSql('ALTER TABLE sales DROP sale_zone_id');
    }
}
