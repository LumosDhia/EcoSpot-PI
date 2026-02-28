<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228203359 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE article CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE consigne CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE ngo_assignment_request CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE notification CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification CHANGE created_by_id created_by_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE ngo_assignment_request CHANGE created_by_id created_by_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE article CHANGE created_by_id created_by_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE consigne CHANGE created_by_id created_by_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE app_user CHANGE created_by_id created_by_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE ticket CHANGE created_by_id created_by_id BINARY(16) NOT NULL');
    }
}
