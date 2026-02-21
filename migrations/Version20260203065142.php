<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203065142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE password_reset_tokens (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, email VARCHAR(250) NOT NULL, token VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX password_reset_tokens_email_unique (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE users CHANGE status status ENUM(\'active\',\'inactive\',\'deleted\'), CHANGE role role ENUM(\'super_admin\',\'admin\',\'editor\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE password_reset_tokens');
        $this->addSql('ALTER TABLE `users` CHANGE status status ENUM(\'active\', \'inactive\', \'deleted\') DEFAULT NULL, CHANGE role role ENUM(\'super_admin\', \'admin\', \'editor\') DEFAULT NULL');
    }
}
