<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250204131507 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307F1A9A7125');
        $this->addSql('ALTER TABLE message ADD recipient_id INT NOT NULL');
        $this->addSql('ALTER TABLE message ALTER chat_id DROP NOT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FE92F8F78 FOREIGN KEY (recipient_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F1A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_B6BD307FE92F8F78 ON message (recipient_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FE92F8F78');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT fk_b6bd307f1a9a7125');
        $this->addSql('DROP INDEX IDX_B6BD307FE92F8F78');
        $this->addSql('ALTER TABLE message DROP recipient_id');
        $this->addSql('ALTER TABLE message ALTER chat_id SET NOT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT fk_b6bd307f1a9a7125 FOREIGN KEY (chat_id) REFERENCES chat (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
