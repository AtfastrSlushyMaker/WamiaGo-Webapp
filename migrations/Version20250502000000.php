<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add facial recognition field to user table
 */
final class Version20250502000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add facial recognition enabled field to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD face_recognition_enabled TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP face_recognition_enabled');
    }
}
