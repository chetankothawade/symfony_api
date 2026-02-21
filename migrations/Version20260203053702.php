<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203053702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `users` (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, uuid CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(250) NOT NULL, phone VARCHAR(250) DEFAULT NULL, password VARCHAR(255) NOT NULL, status ENUM(\'active\',\'inactive\',\'deleted\'), role ENUM(\'super_admin\',\'admin\',\'editor\'), last_login_at DATETIME DEFAULT NULL, last_logout_at DATETIME DEFAULT NULL, last_login_ip VARCHAR(250) DEFAULT NULL, last_login_ua LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX users_email_unique (email), UNIQUE INDEX users_uuid_unique (uuid), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE `users`');
    }
}
