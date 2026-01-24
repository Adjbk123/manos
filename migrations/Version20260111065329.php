<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260111065329 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE billetages (id INT AUTO_INCREMENT NOT NULL, details JSON NOT NULL, total_theorique NUMERIC(15, 2) NOT NULL, total_physique NUMERIC(15, 2) NOT NULL, ecart NUMERIC(15, 2) NOT NULL, session_id INT NOT NULL, UNIQUE INDEX UNIQ_46E532E0613FECDF (session_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE parametres_caisse (id INT AUTO_INCREMENT NOT NULL, heure_rappel_cloture TIME DEFAULT NULL, frequence_rappel INT DEFAULT NULL, bloquer_operations_si_non_cloture TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rapport_sessions (id INT AUTO_INCREMENT NOT NULL, solde_ouverture NUMERIC(15, 2) NOT NULL, solde_theorique_fermeture NUMERIC(15, 2) NOT NULL, solde_confirme_fermeture NUMERIC(15, 2) NOT NULL, total_depots NUMERIC(15, 2) NOT NULL, total_retraits NUMERIC(15, 2) NOT NULL, total_ventes NUMERIC(15, 2) NOT NULL, ecart NUMERIC(15, 2) NOT NULL, session_id INT NOT NULL, compte_id INT NOT NULL, INDEX IDX_4EA07D91613FECDF (session_id), INDEX IDX_4EA07D91F2C56620 (compte_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE session_resume_details (id INT AUTO_INCREMENT NOT NULL, type_besognee VARCHAR(20) NOT NULL, volume NUMERIC(15, 2) NOT NULL, nombre INT NOT NULL, session_id INT NOT NULL, operateur_id INT NOT NULL, INDEX IDX_D32E1CC4613FECDF (session_id), INDEX IDX_D32E1CC43F192FC (operateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE session_resume_globals (id INT AUTO_INCREMENT NOT NULL, valeur_theorique_totale NUMERIC(15, 2) NOT NULL, valeur_confirmee_totale NUMERIC(15, 2) NOT NULL, ecart_total NUMERIC(15, 2) NOT NULL, session_id INT NOT NULL, UNIQUE INDEX UNIQ_CE5F0F54613FECDF (session_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE session_services (id INT AUTO_INCREMENT NOT NULL, started_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, type_fermeture VARCHAR(20) DEFAULT NULL, agent_id INT NOT NULL, session_suivante_id INT DEFAULT NULL, INDEX IDX_F89E81013414710B (agent_id), UNIQUE INDEX UNIQ_F89E8101FC140E3A (session_suivante_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE billetages ADD CONSTRAINT FK_46E532E0613FECDF FOREIGN KEY (session_id) REFERENCES session_services (id)');
        $this->addSql('ALTER TABLE rapport_sessions ADD CONSTRAINT FK_4EA07D91613FECDF FOREIGN KEY (session_id) REFERENCES session_services (id)');
        $this->addSql('ALTER TABLE rapport_sessions ADD CONSTRAINT FK_4EA07D91F2C56620 FOREIGN KEY (compte_id) REFERENCES accounts (id)');
        $this->addSql('ALTER TABLE session_resume_details ADD CONSTRAINT FK_D32E1CC4613FECDF FOREIGN KEY (session_id) REFERENCES session_services (id)');
        $this->addSql('ALTER TABLE session_resume_details ADD CONSTRAINT FK_D32E1CC43F192FC FOREIGN KEY (operateur_id) REFERENCES operators (id)');
        $this->addSql('ALTER TABLE session_resume_globals ADD CONSTRAINT FK_CE5F0F54613FECDF FOREIGN KEY (session_id) REFERENCES session_services (id)');
        $this->addSql('ALTER TABLE session_services ADD CONSTRAINT FK_F89E81013414710B FOREIGN KEY (agent_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE session_services ADD CONSTRAINT FK_F89E8101FC140E3A FOREIGN KEY (session_suivante_id) REFERENCES session_services (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billetages DROP FOREIGN KEY FK_46E532E0613FECDF');
        $this->addSql('ALTER TABLE rapport_sessions DROP FOREIGN KEY FK_4EA07D91613FECDF');
        $this->addSql('ALTER TABLE rapport_sessions DROP FOREIGN KEY FK_4EA07D91F2C56620');
        $this->addSql('ALTER TABLE session_resume_details DROP FOREIGN KEY FK_D32E1CC4613FECDF');
        $this->addSql('ALTER TABLE session_resume_details DROP FOREIGN KEY FK_D32E1CC43F192FC');
        $this->addSql('ALTER TABLE session_resume_globals DROP FOREIGN KEY FK_CE5F0F54613FECDF');
        $this->addSql('ALTER TABLE session_services DROP FOREIGN KEY FK_F89E81013414710B');
        $this->addSql('ALTER TABLE session_services DROP FOREIGN KEY FK_F89E8101FC140E3A');
        $this->addSql('DROP TABLE billetages');
        $this->addSql('DROP TABLE parametres_caisse');
        $this->addSql('DROP TABLE rapport_sessions');
        $this->addSql('DROP TABLE session_resume_details');
        $this->addSql('DROP TABLE session_resume_globals');
        $this->addSql('DROP TABLE session_services');
    }
}
