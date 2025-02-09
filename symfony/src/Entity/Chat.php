<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\DTO\Api\History\Chat\ChatHistoryDTO;
use App\Provider\ChatHistoryProvider;
use App\Repository\ChatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/chat/allChats',
            output: ChatHistoryDTO::class,
            provider: ChatHistoryProvider::class
        )
    ]
)]
#[ORM\Entity(repositoryClass: ChatRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Chat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $type = 'private';

    #[ORM\ManyToOne(targetEntity: Message::class)]
    private ?Message $lastMessage = null;

    #[ORM\OneToMany(
        targetEntity: Message::class,
        mappedBy: 'chat',
        cascade: ['remove'],
        orphanRemoval: true)
    ]
    private Collection $messages;

    #[ORM\OneToMany(targetEntity: ChatPartner::class, mappedBy: 'chat')]
    private Collection $chatPartners;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->chatPartners = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): Chat
    {
        $this->type = $type;

        return $this;
    }

    public function getLastMessage(): ?Message
    {
        return $this->lastMessage;
    }

    public function setLastMessage(?Message $lastMessage): Chat
    {
        $this->lastMessage = $lastMessage;

        return $this;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function setMessages(Collection $messages): Chat
    {
        $this->messages = $messages;

        return $this;
    }

    public function getChatPartners(): Collection
    {
        return $this->chatPartners;
    }

    public function setChatPartners(array $chatPartners): Chat
    {
        foreach ($chatPartners as $chatPartner) {
            $this->chatPartners->add($chatPartner);
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): Chat
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): Chat
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
