<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260114133150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity_con_pay (id INT AUTO_INCREMENT NOT NULL, activity_id VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, source VARCHAR(255) NOT NULL, date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', applied TINYINT(1) NOT NULL, invalidated TINYINT(1) NOT NULL, applied_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_activity_id (activity_id), INDEX idx_activity_date (date), INDEX idx_activity_source (source), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE triggers (id INT AUTO_INCREMENT NOT NULL, trigger_id VARCHAR(255) NOT NULL, aggregate_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, payload LONGTEXT NOT NULL, status VARCHAR(50) NOT NULL, recalculation_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', received_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', applied_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_trigger_aggregate_id (aggregate_id), INDEX idx_trigger_name (name), INDEX idx_trigger_status (status), INDEX idx_recalculation_date (recalculation_date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE activity_con_pay');
        $this->addSql('DROP TABLE triggers');
    }
}
