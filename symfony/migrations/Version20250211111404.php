<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250211111404 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX local_id_sender');
        $this->addSql('CREATE INDEX idx_message_sender_recipient ON message (sender_id, recipient_id)');
        $this->addSql('ALTER INDEX idx_b6bd307ff624b39d RENAME TO idx_message_sender');
        $this->addSql('ALTER INDEX idx_b6bd307fe92f8f78 RENAME TO idx_message_recipient');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_message_sender_recipient');
        $this->addSql('CREATE UNIQUE INDEX local_id_sender ON message (sender_id, local_id)');
        $this->addSql('ALTER INDEX idx_message_recipient RENAME TO idx_b6bd307fe92f8f78');
        $this->addSql('ALTER INDEX idx_message_sender RENAME TO idx_b6bd307ff624b39d');
    }
}
