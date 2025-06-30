<?php

namespace App\Entity;

use App\Repository\ExtensionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExtensionRepository::class)]
class Extension
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $apiId = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $totalCardsMain = null;

    #[ORM\Column]
    private ?int $totalCardsSecret = null;

    #[ORM\ManyToOne(inversedBy: 'extensions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Serie $serie = null;

    #[ORM\OneToMany(mappedBy: 'extension', targetEntity: PokemonCard::class, orphanRemoval: true)]
    private Collection $pokemonCards;

    public function __construct()
    {
        $this->pokemonCards = new ArrayCollection();
    }

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTotalCardsMain(): ?int
    {
        return $this->totalCardsMain;
    }

    public function setTotalCardsMain(int $totalCardsMain): static
    {
        $this->totalCardsMain = $totalCardsMain;

        return $this;
    }

    public function getTotalCardsSecret(): ?int
    {
        return $this->totalCardsSecret;
    }

    public function setTotalCardsSecret(int $totalCardsSecret): static
    {
        $this->totalCardsSecret = $totalCardsSecret;

        return $this;
    }

    public function getSerie(): ?Serie
    {
        return $this->serie;
    }

    public function setSerie(?Serie $serie): static
    {
        $this->serie = $serie;

        return $this;
    }

    /**
     * @return Collection<int, PokemonCard>
     */
    public function getPokemonCards(): Collection
    {
        return $this->pokemonCards;
    }

    public function addPokemonCard(PokemonCard $pokemonCard): static
    {
        if (!$this->pokemonCards->contains($pokemonCard)) {
            $this->pokemonCards->add($pokemonCard);
            $pokemonCard->setExtension($this);
        }

        return $this;
    }

    public function removePokemonCard(PokemonCard $pokemonCard): static
    {
        if ($this->pokemonCards->removeElement($pokemonCard)) {
            // set the owning side to null (unless already changed)
            if ($pokemonCard->getExtension() === $this) {
                $pokemonCard->setExtension(null);
            }
        }

        return $this;
    }
}
