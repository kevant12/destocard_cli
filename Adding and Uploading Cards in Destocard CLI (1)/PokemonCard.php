<?php

namespace App\Entity;

use App\Repository\PokemonCardRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Media;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Extension;

#[ORM\Entity(repositoryClass: PokemonCardRepository::class)]
class PokemonCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['product:read'])]
    private ?string $apiId = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    private ?string $number = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $specialType = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    private ?string $raritySymbol = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    private ?string $rarityText = null;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?bool $isReversePossible = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $subSerie = null;

    #[ORM\ManyToOne(targetEntity: Extension::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['product:read'])]
    private ?Extension $extension = null;

    #[OneToOne(targetEntity: Media::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(nullable: true)]
    #[Groups(['product:read'])]
    private ?Media $image = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApiId(): ?string
    {
        return $this->apiId;
    }

    public function setApiId(string $apiId): static
    {
        $this->apiId = $apiId;

        return $this;
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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getSpecialType(): ?string
    {
        return $this->specialType;
    }

    public function setSpecialType(?string $specialType): static
    {
        $this->specialType = $specialType;

        return $this;
    }

    public function getRaritySymbol(): ?string
    {
        return $this->raritySymbol;
    }

    public function setRaritySymbol(string $raritySymbol): static
    {
        $this->raritySymbol = $raritySymbol;

        return $this;
    }

    public function getRarityText(): ?string
    {
        return $this->rarityText;
    }

    public function setRarityText(string $rarityText): static
    {
        $this->rarityText = $rarityText;

        return $this;
    }

    public function isIsReversePossible(): ?bool
    {
        return $this->isReversePossible;
    }

    public function setIsReversePossible(bool $isReversePossible): static
    {
        $this->isReversePossible = $isReversePossible;

        return $this;
    }

    public function getSubSerie(): ?string
    {
        return $this->subSerie;
    }

    public function setSubSerie(?string $subSerie): static
    {
        $this->subSerie = $subSerie;

        return $this;
    }

    public function getExtension(): ?Extension
    {
        return $this->extension;
    }

    public function setExtension(?Extension $extension): static
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
}