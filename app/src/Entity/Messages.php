<?php

namespace App\Entity;

use App\Repository\MessagesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessagesRepository::class)]
class Messages
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $expeditionDate = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?bool $isRead = false;

    #[ORM\ManyToOne(inversedBy: 'sender')]
    private ?Users $sender = null;

    #[ORM\ManyToOne(inversedBy: 'receper')]
    private ?Users $receper = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getExpeditionDate(): ?\DateTimeImmutable
    {
        return $this->expeditionDate;
    }

    public function setExpeditionDate(\DateTimeImmutable $expeditionDate): static
    {
        $this->expeditionDate = $expeditionDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSender(): ?Users
    {
        return $this->sender;
    }

    public function setSender(?Users $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getReceper(): ?Users
    {
        return $this->receper;
    }

    public function setReceper(?Users $receper): static
    {
        $this->receper = $receper;

        return $this;
    }
}
