<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221232126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article_tag (article_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_919694F97294869C (article_id), INDEX IDX_919694F9BAD26311 (tag_id), PRIMARY KEY(article_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE article_reaction (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, user_id INT NOT NULL, article_id INT NOT NULL, INDEX IDX_F13FF39CA76ED395 (user_id), INDEX IDX_F13FF39C7294869C (article_id), UNIQUE INDEX uniq_article_user (article_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_64C19C15E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_389B7835E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE article_tag ADD CONSTRAINT FK_919694F97294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_tag ADD CONSTRAINT FK_919694F9BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_reaction ADD CONSTRAINT FK_F13FF39CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_reaction ADD CONSTRAINT FK_F13FF39C7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE article ADD views INT DEFAULT 0 NOT NULL, ADD slug VARCHAR(128) NOT NULL, ADD seo_title VARCHAR(255) DEFAULT NULL, ADD seo_description LONGTEXT DEFAULT NULL, ADD seo_keywords LONGTEXT DEFAULT NULL, ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6612469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_23A0E662B36786B ON article (title)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_23A0E66989D9B62 ON article (slug)');
        $this->addSql('CREATE INDEX IDX_23A0E6612469DE2 ON article (category_id)');
        $this->addSql('ALTER TABLE event ADD slug VARCHAR(128) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3BAE0AA7989D9B62 ON event (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article_tag DROP FOREIGN KEY FK_919694F97294869C');
        $this->addSql('ALTER TABLE article_tag DROP FOREIGN KEY FK_919694F9BAD26311');
        $this->addSql('ALTER TABLE article_reaction DROP FOREIGN KEY FK_F13FF39CA76ED395');
        $this->addSql('ALTER TABLE article_reaction DROP FOREIGN KEY FK_F13FF39C7294869C');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE article_tag');
        $this->addSql('DROP TABLE article_reaction');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE tag');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E6612469DE2');
        $this->addSql('DROP INDEX UNIQ_23A0E662B36786B ON article');
        $this->addSql('DROP INDEX UNIQ_23A0E66989D9B62 ON article');
        $this->addSql('DROP INDEX IDX_23A0E6612469DE2 ON article');
        $this->addSql('ALTER TABLE article DROP views, DROP slug, DROP seo_title, DROP seo_description, DROP seo_keywords, DROP category_id');
        $this->addSql('DROP INDEX UNIQ_3BAE0AA7989D9B62 ON event');
        $this->addSql('ALTER TABLE event DROP slug');
    }
}
