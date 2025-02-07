<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250207213048 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX chat_recipient_idx ON message (chat_id, recipient_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX chat_recipient_idx');
    }
}
