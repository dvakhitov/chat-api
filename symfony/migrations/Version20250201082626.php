<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250201082626 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE user_id_seq CASCADE');
        $this->addSql('ALTER TABLE message ALTER return_uniq_id TYPE INT');
        $this->addSql('ALTER TABLE "user" ALTER id DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_id_seq');
        $this->addSql('SELECT setval(\'user_id_seq\', (SELECT MAX(id) FROM "user"))');
        $this->addSql('ALTER TABLE "user" ALTER id SET DEFAULT nextval(\'user_id_seq\')');
        $this->addSql('ALTER TABLE message ALTER return_uniq_id TYPE BIGINT');
    }
}
