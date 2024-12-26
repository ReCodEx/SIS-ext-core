<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241226183622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sis_affiliation (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', event_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', term_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', type VARCHAR(255) NOT NULL, INDEX IDX_2A5DB2B0A76ED395 (user_id), INDEX IDX_2A5DB2B071F7E88B (event_id), INDEX IDX_2A5DB2B0E2C35FC (term_id), UNIQUE INDEX UNIQ_2A5DB2B0A76ED39571F7E88B (user_id, event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sis_course (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', code VARCHAR(255) NOT NULL, caption_cs VARCHAR(255) NOT NULL, caption_en VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_5E2AA02577153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sis_schedule_event (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', term_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', course_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', sis_id VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, day_of_week INT NOT NULL, first_week INT NOT NULL, time INT NOT NULL, length INT NOT NULL, room VARCHAR(255) NOT NULL, fortnight TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_6C55C6CC48DCD8A0 (sis_id), INDEX IDX_6C55C6CCE2C35FC (term_id), INDEX IDX_6C55C6CC591CC992 (course_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sis_term (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', year INT NOT NULL, term INT NOT NULL, beginning DATETIME DEFAULT NULL, end DATETIME DEFAULT NULL, students_from DATETIME NOT NULL, students_until DATETIME NOT NULL, teachers_from DATETIME NOT NULL, teachers_until DATETIME NOT NULL, archive_after DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sis_user (id VARCHAR(255) NOT NULL, login VARCHAR(255) DEFAULT NULL, titles_before_name VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, titles_after_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_C2F16E0CAA08CB10 (login), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', instance_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', sis_id VARCHAR(255) DEFAULT NULL, sis_login VARCHAR(255) DEFAULT NULL, titles_before_name VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, titles_after_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, default_language VARCHAR(32) NOT NULL, token_validity_threshold DATETIME DEFAULT NULL, sis_user_loaded DATETIME DEFAULT NULL, sis_events_loaded DATETIME DEFAULT NULL, recodex_token VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D64948DCD8A0 (sis_id), UNIQUE INDEX UNIQ_8D93D649ACF11CB7 (sis_login), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sis_affiliation ADD CONSTRAINT FK_2A5DB2B0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sis_affiliation ADD CONSTRAINT FK_2A5DB2B071F7E88B FOREIGN KEY (event_id) REFERENCES sis_schedule_event (id)');
        $this->addSql('ALTER TABLE sis_affiliation ADD CONSTRAINT FK_2A5DB2B0E2C35FC FOREIGN KEY (term_id) REFERENCES sis_term (id)');
        $this->addSql('ALTER TABLE sis_schedule_event ADD CONSTRAINT FK_6C55C6CCE2C35FC FOREIGN KEY (term_id) REFERENCES sis_term (id)');
        $this->addSql('ALTER TABLE sis_schedule_event ADD CONSTRAINT FK_6C55C6CC591CC992 FOREIGN KEY (course_id) REFERENCES sis_course (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sis_affiliation DROP FOREIGN KEY FK_2A5DB2B0A76ED395');
        $this->addSql('ALTER TABLE sis_affiliation DROP FOREIGN KEY FK_2A5DB2B071F7E88B');
        $this->addSql('ALTER TABLE sis_affiliation DROP FOREIGN KEY FK_2A5DB2B0E2C35FC');
        $this->addSql('ALTER TABLE sis_schedule_event DROP FOREIGN KEY FK_6C55C6CCE2C35FC');
        $this->addSql('ALTER TABLE sis_schedule_event DROP FOREIGN KEY FK_6C55C6CC591CC992');
        $this->addSql('DROP TABLE sis_affiliation');
        $this->addSql('DROP TABLE sis_course');
        $this->addSql('DROP TABLE sis_schedule_event');
        $this->addSql('DROP TABLE sis_term');
        $this->addSql('DROP TABLE sis_user');
        $this->addSql('DROP TABLE user');
    }
}
