<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250201083518 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message ALTER return_uniq_id TYPE BIGINT');
        $this->addSql('ALTER TABLE message ALTER return_uniq_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message ALTER return_uniq_id TYPE INT');
        $this->addSql('ALTER TABLE message ALTER return_uniq_id SET NOT NULL');
    }
}
