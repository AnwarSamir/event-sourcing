<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260114140326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_read_models (order_id VARCHAR(255) NOT NULL, customer_id VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, is_returnable TINYINT(1) NOT NULL, delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', feedback_window_end_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', tracking_number VARCHAR(255) DEFAULT NULL, return_reason LONGTEXT DEFAULT NULL, refund_amount NUMERIC(10, 2) DEFAULT NULL, items LONGTEXT NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_read_model_order_id (order_id), INDEX idx_read_model_customer_id (customer_id), INDEX idx_read_model_status (status), PRIMARY KEY(order_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE order_read_models');
    }
}
