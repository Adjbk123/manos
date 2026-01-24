<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260124170742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE partners (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(100) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY `FK_82C24DBC11CE312B`');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY `FK_82C24DBC855D3E3D`');
        $this->addSql('DROP INDEX IDX_82C24DBC11CE312B ON loans');
        $this->addSql('DROP INDEX IDX_82C24DBC855D3E3D ON loans');
        $this->addSql('ALTER TABLE loans ADD direction VARCHAR(20) NOT NULL, ADD user_id INT NOT NULL, ADD partner_id INT NOT NULL, DROP lender_id, DROP borrower_id');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBCA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC9393F8FE FOREIGN KEY (partner_id) REFERENCES partners (id)');
        $this->addSql('CREATE INDEX IDX_82C24DBCA76ED395 ON loans (user_id)');
        $this->addSql('CREATE INDEX IDX_82C24DBC9393F8FE ON loans (partner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE partners');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBCA76ED395');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC9393F8FE');
        $this->addSql('DROP INDEX IDX_82C24DBCA76ED395 ON loans');
        $this->addSql('DROP INDEX IDX_82C24DBC9393F8FE ON loans');
        $this->addSql('ALTER TABLE loans ADD lender_id INT NOT NULL, ADD borrower_id INT NOT NULL, DROP direction, DROP user_id, DROP partner_id');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT `FK_82C24DBC11CE312B` FOREIGN KEY (borrower_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT `FK_82C24DBC855D3E3D` FOREIGN KEY (lender_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_82C24DBC11CE312B ON loans (borrower_id)');
        $this->addSql('CREATE INDEX IDX_82C24DBC855D3E3D ON loans (lender_id)');
    }
}
