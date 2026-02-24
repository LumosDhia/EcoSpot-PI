<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224090830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ngo_assignment_request (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, ticket_id INT NOT NULL, ngo_id INT NOT NULL, INDEX IDX_5A785EEE700047D2 (ticket_id), INDEX IDX_5A785EEE526B9FA3 (ngo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ngo_assignment_request ADD CONSTRAINT FK_5A785EEE700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ngo_assignment_request ADD CONSTRAINT FK_5A785EEE526B9FA3 FOREIGN KEY (ngo_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ticket ADD ngo_notes LONGTEXT DEFAULT NULL, ADD assigned_ngo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3CA7D372D FOREIGN KEY (assigned_ngo_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_97A0ADA3CA7D372D ON ticket (assigned_ngo_id)');
        $this->addSql('ALTER TABLE user ADD ngo_description LONGTEXT DEFAULT NULL, CHANGE face_enrolled face_enrolled TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ngo_assignment_request DROP FOREIGN KEY FK_5A785EEE700047D2');
        $this->addSql('ALTER TABLE ngo_assignment_request DROP FOREIGN KEY FK_5A785EEE526B9FA3');
        $this->addSql('DROP TABLE ngo_assignment_request');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3CA7D372D');
        $this->addSql('DROP INDEX IDX_97A0ADA3CA7D372D ON ticket');
        $this->addSql('ALTER TABLE ticket DROP ngo_notes, DROP assigned_ngo_id');
        $this->addSql('ALTER TABLE `user` DROP ngo_description, CHANGE face_enrolled face_enrolled TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
