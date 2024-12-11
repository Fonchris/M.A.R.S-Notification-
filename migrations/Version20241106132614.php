<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241106132614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
{
    // Check for existing columns before adding
    $table = $schema->getTable('notification');
    if (!$table->hasColumn('sms_destination')) {
        $this->addSql('ALTER TABLE notification ADD sms_destination VARCHAR(255) DEFAULT NULL');
    }
    if (!$table->hasColumn('whatsapp_destination')) {
        $this->addSql('ALTER TABLE notification ADD whatsapp_destination VARCHAR(255) DEFAULT NULL');
    }
}
}
