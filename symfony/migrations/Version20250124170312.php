<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250124170312 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_8d93d6498d130e14');
        $this->addSql('ALTER TABLE "user" ADD is_email_verified BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" DROP chat_user_uuid');
        $this->addSql('ALTER TABLE "user" ALTER last_name DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD chat_user_uuid UUID NOT NULL');
        $this->addSql('ALTER TABLE "user" DROP is_email_verified');
        $this->addSql('ALTER TABLE "user" ALTER last_name SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN "user".chat_user_uuid IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX uniq_8d93d6498d130e14 ON "user" (chat_user_uuid)');
    }
}
