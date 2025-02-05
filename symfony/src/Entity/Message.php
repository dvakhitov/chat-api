<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\DTO\Api\History\Message\MessagesHistoryDTO;
use App\Provider\MessagesHistoryProvider;
use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\Link;

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
#[ORM\HasLifecycleCallbacks]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Chat::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Chat $chat = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sender = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messages')]
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

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }


    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
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
        return $this->isRead ? 'read' : 'sent';
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
} 
