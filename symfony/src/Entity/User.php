<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(
    name: "user_email_idx",
    columns: ["email"],
)]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups(['websocket'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['websocket'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['websocket'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['websocket'])]
    private ?string $email = null;

    #[ORM\Column]
    private bool $isPremium = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender', fetch: 'LAZY')]
    private Collection $messagesSent;


    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'recipient', fetch: 'LAZY')]
    private Collection $messagesReceived;

    #[ORM\OneToMany(targetEntity: ChatPartner::class, mappedBy: 'user')]
    private Collection $chatPartners;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $birthday = null;
    #[ORM\Column(nullable: true)]
    private ?bool $isEmailVerified = null;

    public function __construct()
    {
        $this->messagesSent = new ArrayCollection();
        $this->chatPartners = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function isPremium(): bool
    {
        return $this->isPremium;
    }

    public function setIsPremium(bool $isPremium): void
    {
        $this->isPremium = $isPremium;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): void
    {
        $this->photoUrl = $photoUrl;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getMessagesSent(): Collection
    {
        return $this->messagesSent;
    }

    public function setMessagesSent(Collection $messagesSent): void
    {
        $this->messagesSent = $messagesSent;
    }

    public function getMessagesReceived(): Collection
    {
        return $this->messagesReceived;
    }

    public function setMessagesReceived(Collection $messagesReceived): void
    {
        $this->messagesReceived = $messagesReceived;
    }

    public function getChatPartners(): Collection
    {
        return $this->chatPartners;
    }

    public function setChatPartners(Collection $chatPartners): void
    {
        $this->chatPartners = $chatPartners;
    }

    public function isEmailVerified(): ?bool
    {
        return $this->isEmailVerified;
    }

    public function setIsEmailVerified(?bool $isEmailVerified): static
    {
        $this->isEmailVerified = $isEmailVerified;

        return $this;
    }

    public function getIsEmailVerified(): ?bool
    {
        return $this->isEmailVerified;
    }

    public function getBirthday(): ?string
    {
        return $this->birthday;
    }

    public function setBirthday(?string $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * @return list<string>
     * @see UserInterface
     *
     */
    public function getRoles(): array
    {
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }
}
