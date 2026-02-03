<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203082312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users CHANGE status status ENUM(\'active\',\'inactive\',\'deleted\'), CHANGE role role ENUM(\'super_admin\',\'admin\',\'editor\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `users` CHANGE status status ENUM(\'active\', \'inactive\', \'deleted\') DEFAULT NULL, CHANGE role role ENUM(\'super_admin\', \'admin\', \'editor\') DEFAULT NULL');
    }
}
