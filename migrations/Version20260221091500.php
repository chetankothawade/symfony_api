<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221091500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create invoice manager RBAC tables and seed base permissions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE modules (
    id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
    uuid CHAR(36) NOT NULL,
    parent_id BIGINT UNSIGNED DEFAULT NULL,
    name VARCHAR(50) NOT NULL,
    url VARCHAR(100) DEFAULT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    seq_no INT DEFAULT NULL,
    is_sub_module ENUM('Y', 'N') DEFAULT 'N' NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active' NOT NULL,
    is_permission ENUM('Y', 'N') DEFAULT 'N' NOT NULL,
    created_at TIMESTAMP DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT NULL,
    UNIQUE INDEX modules_name_unique (name),
    UNIQUE INDEX modules_uuid_unique (uuid),
    INDEX modules_parent_id_foreign (parent_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
    action VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active' NOT NULL,
    created_at TIMESTAMP DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE module_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT NULL,
    UNIQUE INDEX unique_module_permission (module_id, permission_id),
    INDEX module_permissions_permission_id_foreign (permission_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE role_modules (
    id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
    role ENUM('super_admin', 'admin', 'editor') NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT NULL,
    UNIQUE INDEX role_module_unique (role, module_id),
    INDEX role_modules_module_id_foreign (module_id),
    INDEX role_modules_role_index (role),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE user_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    module_permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT NULL,
    UNIQUE INDEX unique_user_permission (user_id, module_permission_id),
    INDEX user_permissions_module_permission_id_foreign (module_permission_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $this->addSql('ALTER TABLE modules ADD CONSTRAINT modules_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES modules (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_permissions ADD CONSTRAINT module_permissions_module_id_foreign FOREIGN KEY (module_id) REFERENCES modules (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_permissions ADD CONSTRAINT module_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_modules ADD CONSTRAINT role_modules_module_id_foreign FOREIGN KEY (module_id) REFERENCES modules (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT user_permissions_module_permission_id_foreign FOREIGN KEY (module_permission_id) REFERENCES module_permissions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT user_permissions_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        $this->addSql(<<<'SQL'
INSERT INTO permissions (id, action, status, created_at, updated_at) VALUES
    (1, 'view', 'active', '2025-11-27 23:50:07', '2025-11-27 23:50:07'),
    (2, 'create', 'active', '2025-11-27 23:50:07', '2025-11-27 23:50:07'),
    (3, 'edit', 'active', '2025-11-27 23:50:07', '2025-11-27 23:50:07'),
    (4, 'delete', 'active', '2025-11-27 23:50:07', '2025-11-27 23:50:07'),
    (5, 'status', 'active', '2025-11-27 23:50:07', '2025-11-27 23:50:07')
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_permissions DROP FOREIGN KEY user_permissions_module_permission_id_foreign');
        $this->addSql('ALTER TABLE user_permissions DROP FOREIGN KEY user_permissions_user_id_foreign');
        $this->addSql('ALTER TABLE role_modules DROP FOREIGN KEY role_modules_module_id_foreign');
        $this->addSql('ALTER TABLE module_permissions DROP FOREIGN KEY module_permissions_module_id_foreign');
        $this->addSql('ALTER TABLE module_permissions DROP FOREIGN KEY module_permissions_permission_id_foreign');
        $this->addSql('ALTER TABLE modules DROP FOREIGN KEY modules_parent_id_foreign');

        $this->addSql('DROP TABLE user_permissions');
        $this->addSql('DROP TABLE role_modules');
        $this->addSql('DROP TABLE module_permissions');
        $this->addSql('DROP TABLE permissions');
        $this->addSql('DROP TABLE modules');
    }
}
