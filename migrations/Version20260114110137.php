<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260114110137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE domain_events (id INT AUTO_INCREMENT NOT NULL, aggregate_id VARCHAR(255) NOT NULL, event_type VARCHAR(255) NOT NULL, event_data LONGTEXT NOT NULL, occurred_on DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', sequence INT NOT NULL, INDEX idx_aggregate_id (aggregate_id), INDEX idx_event_type (event_type), INDEX idx_occurred_on (occurred_on), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_snapshots (id INT AUTO_INCREMENT NOT NULL, order_id VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, is_returnable TINYINT(1) NOT NULL, delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', feedback_window_end_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', version INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_F1163DC58D9F6D38 (order_id), INDEX idx_order_id (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE domain_events');
        $this->addSql('DROP TABLE order_snapshots');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
