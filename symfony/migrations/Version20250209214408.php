<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250209214408 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX local_id_sender ON message (sender_id, local_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX local_id_sender');
    }
}
