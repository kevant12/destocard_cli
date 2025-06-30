<?php

namespace App\Entity;

use App\Repository\PokemonCardRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Media;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PokemonCardRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PokemonCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    #[Assert\NotBlank(message: "Le numéro de carte ne peut pas être vide.")]
    #[Assert\Length(max: 255, maxMessage: "Le numéro de carte ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $number = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    #[Assert\NotBlank(message: "Le nom de la carte ne peut pas être vide.")]
    #[Assert\Length(min: 2, max: 255, minMessage: "Le nom de la carte doit contenir au moins {{ limit }} caractères.", maxMessage: "Le nom de la carte ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    #[Assert\NotBlank(message: "La rareté ne peut pas être vide.")]
    #[Assert\Length(max: 255, maxMessage: "La rareté ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $rarity = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read'])]
    #[Assert\Length(max: 255, maxMessage: "L'extension ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $extension = null;

    #[OneToOne(targetEntity: Media::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(nullable: true)]
    #[Groups(['product:read'])]
    private ?Media $image = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "La note en étoile ne peut pas être vide.")]
    #[Assert\Range(min: 0, max: 5, notInRangeMessage: "La note en étoile doit être comprise entre {{ min }} et {{ max }}.")]
    private ?int $starRating = null;

    #[ORM\Column]
    private ?bool $holo = null;

    #[ORM\Column]
    private ?bool $reverse = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    // public function getNomEn(): ?string
    // {
    //     return $this->nomEn;
    // }

    // public function setNomEn(?string $nomEn): static
    // {
    //     $this->nomEn = $nomEn;

    //     return $this;
    // }

    // public function getNomJp(): ?string
    // {
    //     return $this->nomJp;
    // }

    // public function setNomJp(?string $nomJp): static
    // {
    //     $this->nomJp = $nomJp;

    //     return $this;
    // }

    public function getRarity(): ?string
    {
        return $this->rarity;
    }

    public function setRarity(string $rarity): static
    {
        $this->rarity = $rarity;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): static
    {
        $this->extension = $extension;

        return $this;
    }

    public function getImage(): ?Media
    {
        return $this->image;
    }

    public function setImage(?Media $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getStarRating(): ?string
    {
        return $this->starRating;
    }

    public function setStarRating(string $starRating): static
    {
        $this->starRating = $starRating;

        return $this;
    }

    public function isHolo(): ?bool
    {
        return $this->holo;
    }

    public function setHolo(bool $holo): static
    {
        $this->holo = $holo;

        return $this;
    }

    public function isReverse(): ?bool
    {
        return $this->reverse;
    }

    public function setReverse(bool $reverse): static
    {
        $this->reverse = $reverse;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
