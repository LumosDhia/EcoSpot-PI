<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301203513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Update existing NULL values to a default admin user before applying NOT NULL
        $adminId = '0x019CA478D1D377B4AE80630614A4A0FD';
        $this->addSql("UPDATE app_user SET created_by_id = $adminId WHERE created_by_id IS NULL");
        $this->addSql("UPDATE article SET created_by_id = $adminId WHERE created_by_id IS NULL");
        $this->addSql("UPDATE consigne SET created_by_id = $adminId WHERE created_by_id IS NULL");
        $this->addSql("UPDATE ngo_assignment_request SET created_by_id = $adminId WHERE created_by_id IS NULL");
        $this->addSql("UPDATE notification SET created_by_id = $adminId WHERE created_by_id IS NULL");
        $this->addSql("UPDATE ticket SET created_by_id = $adminId WHERE created_by_id IS NULL");

        $this->addSql('ALTER TABLE app_user CHANGE created_by_id created_by_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE article CHANGE created_by_id created_by_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE consigne CHANGE created_by_id created_by_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE ngo_assignment_request CHANGE created_by_id created_by_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE notification CHANGE created_by_id created_by_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE ticket CHANGE created_by_id created_by_id BINARY(16) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE ngo_assignment_request CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE article CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE consigne CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL');
    }
}
