<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\DTO\Api\History\Message\MessagesHistoryDTO;
use App\Helper\IntegersToIndex;
use App\Provider\MessagesHistoryProvider;
use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\Link;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/chat/message/history/{chatPartnerId<\d+>}',
            output: MessagesHistoryDTO::class,
            name: 'chat_message_history',
            provider: MessagesHistoryProvider::class
        )
    ],
    uriVariables: [
        'chatPartnerId' => new Link(
            fromClass: MessagesHistoryDTO::class,
            identifiers: ['chatPartnerId']
        )
    ]
)]
#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(
    name: 'message',
    indexes: [
        new ORM\Index(name: self::CHAT_RECIPIENT_IDX, columns: ['chat_id', 'recipient_id']),
        new ORM\Index(name: self::IDX_MESSAGE_SENDER, columns: ['sender_id']),
        new ORM\Index(name: self::IDX_MESSAGE_RECIPIENT, columns: ['recipient_id']),
        new ORM\Index(name: self::IDX_MESSAGE_SENDER_RECIPIENT, columns: ['sender_id', 'recipient_id']),
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: self::UNIQUE_LOCAL_ID_SENDER, columns: ['sender_id', 'local_id'])
    ]
)]
#[
    ORM\Index(name: self::CHAT_RECIPIENT_IDX, columns: ['chat_id', 'recipient_id']),
    ORM\Index(name: self::IDX_MESSAGE_SENDER, columns: ['sender_id']),
    ORM\Index(name: self::IDX_MESSAGE_RECIPIENT, columns: ['recipient_id']),
    ORM\Index(name: self::IDX_MESSAGE_SENDER_RECIPIENT, columns: ['sender_id', 'recipient_id']),
]
#[UniqueEntity(fields: ['localId', 'sender'], message: 'Message already sent')]
#[ORM\HasLifecycleCallbacks]
class Message
{
    // Константы для статусов
    public const STATUS_READ = 'read';
    public const STATUS_SENT = 'sent';

    // Константы для имен индексов
    public const CHAT_RECIPIENT_IDX = 'chat_recipient_idx';
    public const IDX_MESSAGE_SENDER = 'idx_message_sender';
    public const IDX_MESSAGE_RECIPIENT = 'idx_message_recipient';
    public const IDX_MESSAGE_SENDER_RECIPIENT = 'idx_message_sender_recipient';
    public const UNIQUE_LOCAL_ID_SENDER = 'local_id_sender';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Chat::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Chat $chat = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messagesSent')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sender = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messagesReceived')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $recipient = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(nullable: false, options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetimetz', nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?string $localId = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $chatIndex = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChat(): ?Chat
    {
        return $this->chat;
    }

    public function setChat(?Chat $chat): static
    {
        $this->chat = $chat;
        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->chatIndex = $this->createChatIndex();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
    }

    private function createChatIndex(): string
    {
        $usersIds = [];
        $usersIds[] = $this->sender->getId();
        $usersIds[] = $this->recipient->getId();

        return IntegersToIndex::convert($usersIds);
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getLocalId(): ?string
    {
        return $this->localId;
    }

    public function setLocalId(string $localId): static
    {
        $this->localId = $localId;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->isRead ? self::STATUS_READ : self::STATUS_SENT;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getChatIndex(): ?string
    {
        return $this->chatIndex;
    }

}
