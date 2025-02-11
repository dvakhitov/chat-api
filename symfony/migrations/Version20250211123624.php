<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250211123624 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Обновляем поле chat_index для всех записей
        $this->addSql(<<<SQL
            UPDATE message
            SET chat_index = 
                CASE
                    WHEN sender_id < recipient_id THEN sender_id::text || '_' || recipient_id::text
                    ELSE recipient_id::text || '_' || sender_id::text
                END
        SQL);
    }

    public function down(Schema $schema): void
    {
        // При необходимости можно откатить обновление, например, обнулив поле chat_index.
        // Если поле не должно быть пустым, можно оставить метод пустым.
        $this->addSql("UPDATE message SET chat_index = NULL");
    }
}
